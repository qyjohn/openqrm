<!doctype html>
<html lang="en">
<head>
	<title>KVM manager</title>
	<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
	<link rel="stylesheet" type="text/css" href="kvm-storage.css" />
	<link type="text/css" href="/openqrm/base/js/jquery/development-bundle/themes/smoothness/ui.all.css" rel="stylesheet" />
	<script type="text/javascript" src="/openqrm/base/js/jquery/js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="/openqrm/base/js/jquery/js/jquery-ui-1.7.1.custom.min.js"></script>
<style type="text/css">
.ui-progressbar-value {
	background-image: url(/openqrm/base/img/progress.gif);
}
#progressbar {
	position: absolute;
	left: 150px;
	top: 250px;
	width: 400px;
	height: 20px;
}
</style>
</head>
<body>
<div id="progressbar">
</div>

<?php
/*
  This file is part of openQRM.

	openQRM is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License version 2
	as published by the Free Software Foundation.

	openQRM is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with openQRM.  If not, see <http://www.gnu.org/licenses/>.

	Copyright 2009, Matthias Rechenburg <matt@openqrm.com>
*/


// error_reporting(E_ALL);
$thisfile = basename($_SERVER['PHP_SELF']);
$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
$BaseDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/';
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/deployment.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/include/htmlobject.inc.php";

$kvm_server_id = htmlobject_request('kvm_server_id');
$kvm_vm_mac = htmlobject_request('kvm_vm_mac');
$kvm_vm_mac_ar = htmlobject_request('kvm_vm_mac_ar');
$kvm_migrate_to_id_ar = htmlobject_request('kvm_migrate_to_id');
$action=htmlobject_request('action');
global $kvm_server_id;
global $kvm_vm_mac;
global $kvm_vm_mac_ar;
global $kvm_migrate_to_id_ar;
$refresh_delay=1;
$refresh_loop_max=35;
$refresh_migrate_loop_max=360;

$event = new event();
global $event;
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $OPENQRM_SERVER_BASE_DIR;



function redirect($strMsg, $currenttab = 'tab0', $url = '') {
	global $thisfile;
	global $kvm_server_id;
	if($url == '') {
		$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab='.$currenttab.'&kvm_server_id='.$kvm_server_id;
	}
	echo "<meta http-equiv=\"refresh\" content=\"0; URL=$url\">";
	exit;
}

function wait_for_statfile($sfile, $migration) {
	global $refresh_delay;
	global $refresh_loop_max;
	global $refresh_migrate_loop_max;
	if ($migration) {
		$refresh_max = $refresh_migrate_loop_max;
	} else {
		$refresh_max = $refresh_loop_max;
	}
	$refresh_loop=0;
	while (!file_exists($sfile)) {
		sleep($refresh_delay);
		$refresh_loop++;
		flush();
		if ($refresh_loop > $refresh_max)  {
			return false;
		}
	}
	return true;
}

function show_progressbar() {
?>
	<script type="text/javascript">
		$("#progressbar").progressbar({
			value: 100
		});
		var options = {};
		$("#progressbar").effect("shake",options,2000,null);
	</script>
<?php
		flush();
}

function kvm_htmlobject_select($name, $value, $title = '', $selected = '') {
	$html = new htmlobject_select();
	$html->name = $name;
	$html->title = $title;
	$html->selected = $selected;
	$html->text_index = array("value" => "value", "text" => "label");
	$html->text = $value;
	return $html->get_string();
}


// check if we got some actions to do
$strMsg = '';
if(htmlobject_request('action') != '') {
	if ($OPENQRM_USER->role == "administrator") {
		switch (htmlobject_request('action')) {
			case 'select':
				if (isset($_REQUEST['identifier'])) {
					foreach($_REQUEST['identifier'] as $kvm_server_id) {
						show_progressbar();
						$kvm_appliance = new appliance();
						$kvm_appliance->get_instance_by_id($kvm_server_id);
						$kvm_server = new resource();
						$kvm_server->get_instance_by_id($kvm_appliance->resources);
						$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/kvm-storage/bin/openqrm-kvm-storage-vm post_vm_list -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
						// remove current stat file
						$kvm_server_resource_id = $kvm_server->id;
						$statfile="kvm-stat/".$kvm_server_resource_id.".vm_list";
						if (file_exists($statfile)) {
							unlink($statfile);
						}
						// send command
						$kvm_server->send_command($kvm_server->ip, $resource_command);
						// and wait for the resulting statfile
						if (!wait_for_statfile($statfile, false)) {
							$strMsg .= "Error during refreshing vm list ! Please check the Event-Log<br>";
						} else {
							$strMsg .="Refreshing vm list<br>";
						}
						redirect($strMsg, "tab0");
						exit(0);
					}
				}
				break;

			case 'reload':
				show_progressbar();
				$kvm_appliance = new appliance();
				$kvm_appliance->get_instance_by_id($kvm_server_id);
				$kvm_server = new resource();
				$kvm_server->get_instance_by_id($kvm_appliance->resources);
				$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/kvm-storage/bin/openqrm-kvm-storage-vm post_vm_list -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
				// remove current stat file
				$kvm_server_resource_id = $kvm_server->id;
				$statfile="kvm-stat/".$kvm_server_resource_id.".vm_list";
				if (file_exists($statfile)) {
					unlink($statfile);
				}
				// send command
				$kvm_server->send_command($kvm_server->ip, $resource_command);
				// and wait for the resulting statfile
				if (!wait_for_statfile($statfile, false)) {
					$strMsg .= "Error during refreshing vm list ! Please check the Event-Log<br>";
				} else {
					$strMsg .="Refreshing vm list<br>";
				}
				redirect($strMsg, "tab0");
				break;


			case 'start':
				if (isset($_REQUEST['identifier'])) {
					foreach($_REQUEST['identifier'] as $kvm_server_name) {
						show_progressbar();
						$kvm_appliance = new appliance();
						$kvm_appliance->get_instance_by_id($kvm_server_id);
						$kvm_server = new resource();
						$kvm_server->get_instance_by_id($kvm_appliance->resources);
						$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/kvm-storage/bin/openqrm-kvm-storage-vm start -n $kvm_server_name -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
						// remove current stat file
						$kvm_server_resource_id = $kvm_server->id;
						$statfile="kvm-stat/".$kvm_server_resource_id.".vm_list";
						if (file_exists($statfile)) {
							unlink($statfile);
						}
						// send command
						$kvm_server->send_command($kvm_server->ip, $resource_command);
						// and wait for the resulting statfile
						if (!wait_for_statfile($statfile, false)) {
							$strMsg .= "Error during starting $kvm_server_name ! Please check the Event-Log<br>";
						} else {
							$strMsg .="Starting $kvm_server_name <br>";
						}
					}
					redirect($strMsg, "tab0");
				} else {
					$strMsg ="No virtual machine selected<br>";
					redirect($strMsg, "tab0");
				}
				break;


			case 'stop':
				if (isset($_REQUEST['identifier'])) {
					foreach($_REQUEST['identifier'] as $kvm_server_name) {
						show_progressbar();
						$kvm_appliance = new appliance();
						$kvm_appliance->get_instance_by_id($kvm_server_id);
						$kvm_server = new resource();
						$kvm_server->get_instance_by_id($kvm_appliance->resources);

						// get vnc config
						$kvm_vm_vnc_config_arr = htmlobject_request('kvm_vm_vnc');
						$kvm_vm_vnc_config = $kvm_vm_vnc_config_arr[$kvm_server_name];
						$ksep = strpos($kvm_vm_vnc_config, ":");
						$kvm_vnc_host_ip = substr($kvm_vm_vnc_config, 0, $ksep);
						$kvm_vnc_vm_port = substr($kvm_vm_vnc_config, $ksep+1);
						// get the vm resource
						$kvm_vm_mac = $kvm_vm_mac_ar[$kvm_server_name];
						$kvm_resource = new resource();
						$kvm_resource->get_instance_by_mac($kvm_vm_mac);
						$kvm_vm_id=$kvm_resource->id;

						// before we stop the vm we provide a hook for the remote-console plugins to stop the vm console
						// check if we have a plugin implementing the remote console
						$plugin = new plugin();
						$enabled_plugins = $plugin->enabled();
						foreach ($enabled_plugins as $index => $plugin_name) {
							$plugin_remote_console_running = "$RootDir/plugins/$plugin_name/.running";
							$plugin_remote_console_hook = "$RootDir/plugins/$plugin_name/openqrm-$plugin_name-remote-console-hook.php";
							if (file_exists($plugin_remote_console_hook)) {
								if (file_exists($plugin_remote_console_running)) {
									$event->log("console", $_SERVER['REQUEST_TIME'], 5, "kvm-storage-manager.php", "Found plugin $plugin_name providing a remote console.", "", "", 0, 0, $kvm_resource->id);
									require_once "$plugin_remote_console_hook";
									$plugin_remote_console_function="openqrm_"."$plugin_name"."_disable_remote_console";
									$plugin_remote_console_function=str_replace("-", "_", $plugin_remote_console_function);
									$plugin_remote_console_function($kvm_server->ip, $kvm_vnc_vm_port, $kvm_vm_id, $kvm_vm_mac, $kvm_server_name);
									$strMsg .="Stopping the remote console to $kvm_server_name on Host $kvm_server->ip<br>";
								}
							}
						}


						$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/kvm-storage/bin/openqrm-kvm-storage-vm stop -n $kvm_server_name -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
						// remove current stat file
						$kvm_server_resource_id = $kvm_server->id;
						$statfile="kvm-stat/".$kvm_server_resource_id.".vm_list";
						if (file_exists($statfile)) {
							unlink($statfile);
						}
						// send command
						$kvm_server->send_command($kvm_server->ip, $resource_command);
						// and wait for the resulting statfile
						if (!wait_for_statfile($statfile, false)) {
							$strMsg .= "Error during stopping $kvm_server_name ! Please check the Event-Log<br>";
						} else {
							$strMsg .="Stopping $kvm_server_name <br>";
						}
					}
					redirect($strMsg, "tab0");
				} else {
					$strMsg ="No virtual machine selected<br>";
					redirect($strMsg, "tab0");
				}
				break;

			case 'restart':
				if (isset($_REQUEST['identifier'])) {
					foreach($_REQUEST['identifier'] as $kvm_server_name) {
						show_progressbar();
						$kvm_appliance = new appliance();
						$kvm_appliance->get_instance_by_id($kvm_server_id);
						$kvm_server = new resource();
						$kvm_server->get_instance_by_id($kvm_appliance->resources);
						$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/kvm-storage/bin/openqrm-kvm-storage-vm reboot -n $kvm_server_name -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
						// remove current stat file
						$kvm_server_resource_id = $kvm_server->id;
						$statfile="kvm-stat/".$kvm_server_resource_id.".vm_list";
						if (file_exists($statfile)) {
							unlink($statfile);
						}
						// send command
						$kvm_server->send_command($kvm_server->ip, $resource_command);
						// and wait for the resulting statfile
						if (!wait_for_statfile($statfile, false)) {
							$strMsg .= "Error during restarting $kvm_server_name ! Please check the Event-Log<br>";
						} else {
							$strMsg .="Restarting $kvm_server_name <br>";
						}
					}
					redirect($strMsg, "tab0");
				} else {
					$strMsg ="No virtual machine selected<br>";
					redirect($strMsg, "tab0");
				}
				break;

			case 'delete':
				if (isset($_REQUEST['identifier'])) {
					foreach($_REQUEST['identifier'] as $kvm_server_name) {
						show_progressbar();
						// check if the resource still belongs to an appliance, if yes we do not remove it
						$kvm_vm_mac = $kvm_vm_mac_ar[$kvm_server_name];
						$kvm_resource = new resource();
						$kvm_resource->get_instance_by_mac($kvm_vm_mac);
						$kvm_vm_id=$kvm_resource->id;
						$resource_is_used_by_appliance = "";
						$remove_error = 0;
						$appliance = new appliance();
						$appliance_id_list = $appliance->get_all_ids();
						foreach($appliance_id_list as $appliance_list) {
							$appliance_id = $appliance_list['appliance_id'];
							$app_resource_remove_check = new appliance();
							$app_resource_remove_check->get_instance_by_id($appliance_id);
							if ($app_resource_remove_check->resources == $kvm_vm_id) {
								$resource_is_used_by_appliance .= $appliance_id." ";
								$remove_error = 1;
							}
						}
						if ($remove_error == 1) {
							$strMsg .= "VM Resource id ".$kvm_vm_id." is used by appliance(s): ".$resource_is_used_by_appliance." <br>";
							$strMsg .= "Not removing VM resource id ".$kvm_vm_id." !<br>";
							continue;
						}
						// remove vm
						$kvm_appliance = new appliance();
						$kvm_appliance->get_instance_by_id($kvm_server_id);
						$kvm_server = new resource();
						$kvm_server->get_instance_by_id($kvm_appliance->resources);
						$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/kvm-storage/bin/openqrm-kvm-storage-vm delete -n $kvm_server_name -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
						// remove current stat file
						$kvm_server_resource_id = $kvm_server->id;
						$statfile="kvm-stat/".$kvm_server_resource_id.".vm_list";
						if (file_exists($statfile)) {
							unlink($statfile);
						}
						// send command
						$kvm_server->send_command($kvm_server->ip, $resource_command);
						// we should remove the resource of the vm !
						$kvm_resource->remove($kvm_vm_id, $kvm_vm_mac);
						// and wait for the resulting statfile
						if (!wait_for_statfile($statfile, false)) {
							$strMsg .= "Error during removing $kvm_server_name ! Please check the Event-Log<br>";
						} else {
							$strMsg .="Removed $kvm_server_name and its resource $kvm_vm_id<br>";
						}
					}
					redirect($strMsg, "tab0");
				} else {
					$strMsg ="No virtual machine selected<br>";
					redirect($strMsg, "tab0");
				}
				break;



			case 'console':
				if (isset($_REQUEST['identifier'])) {
					foreach($_REQUEST['identifier'] as $kvm_server_name) {
						show_progressbar();
						// get the vnc parameter per vm
						$kvm_vm_vnc_config_arr = htmlobject_request('kvm_vm_vnc');
						$kvm_vm_vnc_config = $kvm_vm_vnc_config_arr[$kvm_server_name];
						$ksep = strpos($kvm_vm_vnc_config, ":");
						$kvm_vnc_host_ip = substr($kvm_vm_vnc_config, 0, $ksep);
						$kvm_vnc_vm_port = substr($kvm_vm_vnc_config, $ksep+1);
						// get the resource
						$kvm_vm_mac = $kvm_vm_mac_ar[$kvm_server_name];
						$kvm_resource = new resource();
						$kvm_resource->get_instance_by_mac($kvm_vm_mac);
						$kvm_vm_id=$kvm_resource->id;

						// get the host
						$kvm_appliance = new appliance();
						$kvm_appliance->get_instance_by_id($kvm_server_id);
						$kvm_server = new resource();
						$kvm_server->get_instance_by_id($kvm_appliance->resources);

						// check if we have a plugin implementing the remote console
						$plugin = new plugin();
						$enabled_plugins = $plugin->enabled();
						foreach ($enabled_plugins as $index => $plugin_name) {
							$plugin_remote_console_running = "$RootDir/plugins/$plugin_name/.running";
							$plugin_remote_console_hook = "$RootDir/plugins/$plugin_name/openqrm-$plugin_name-remote-console-hook.php";
							if (file_exists($plugin_remote_console_hook)) {
								if (file_exists($plugin_remote_console_running)) {
									$event->log("console", $_SERVER['REQUEST_TIME'], 5, "kvm-storage-manager.php", "Found plugin $plugin_name providing a remote console.", "", "", 0, 0, $kvm_resource->id);
									require_once "$plugin_remote_console_hook";
									$plugin_remote_console_function="openqrm_"."$plugin_name"."_remote_console";
									$plugin_remote_console_function=str_replace("-", "_", $plugin_remote_console_function);
									$plugin_remote_console_function($kvm_server->ip, $kvm_vnc_vm_port, $kvm_vm_id, $kvm_vm_mac, $kvm_server_name);
									$strMsg .="Opening a remote console to $kvm_server_name on $kvm_server->ip : $kvm_vnc_vm_port<br>";
								}
							}
						}


					}
					redirect($strMsg, "tab0");
				} else {
					$strMsg ="No virtual machine selected<br>";
					redirect($strMsg, "tab0");
				}
				break;


			case 'migrate':
				if (isset($_REQUEST['identifier'])) {
					foreach($_REQUEST['identifier'] as $kvm_server_name) {
						show_progressbar();
						// gather some infos
						$kvm_vm_mac = $kvm_vm_mac_ar[$kvm_server_name];
						$kvm_resource = new resource();
						$kvm_resource->get_instance_by_mac($kvm_vm_mac);
						$kvm_vm_id=$kvm_resource->id;
						// start as incoming on the destination host
						if (!isset($kvm_migrate_to_id_ar[$kvm_server_name])) {
							continue;
						}
						// remove current stat file
						$statfile="kvm-stat/".$kvm_server_name.".vm_migrated_successfully";
						if (file_exists($statfile)) {
							unlink($statfile);
						}
						// start as incoming on the destination host
						$kvm_destination_host_resource_id = $kvm_migrate_to_id_ar[$kvm_server_name];
						$kvm_destination_host_resource = new resource();
						$kvm_destination_host_resource->get_instance_by_id($kvm_destination_host_resource_id);
						$kvm_destination_host_resource_ip = $kvm_destination_host_resource->ip;
						$resource_command_destination = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/kvm-storage/bin/openqrm-kvm-storage-vm start_as_incoming -n ".$kvm_server_name." -j ".$kvm_vm_id." -u ".$OPENQRM_ADMIN->name." -p ".$OPENQRM_ADMIN->password;
						$kvm_destination_host_resource->send_command($kvm_destination_host_resource_ip, $resource_command_destination);
						sleep(5);
						// send migrate to source host
						$kvm_appliance = new appliance();
						$kvm_appliance->get_instance_by_id($kvm_server_id);
						$kvm_server = new resource();
						$kvm_server->get_instance_by_id($kvm_appliance->resources);
						$resource_command=$OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/kvm-storage/bin/openqrm-kvm-storage-vm migrate -n ".$kvm_server_name." -j ".$kvm_vm_id." -k ".$kvm_destination_host_resource_ip." -u ".$OPENQRM_ADMIN->name." -p ".$OPENQRM_ADMIN->password;
						$kvm_server->send_command($kvm_server->ip, $resource_command);
						// wait for statfile to appear again
						if (!wait_for_statfile($statfile, true)) {
							$strMsg .= "Error while migrating KVM-Storage VM $kvm_server_name to Host resource $kvm_destination_host_resource_id! Please check the Event-Log<br>";
							continue;
						} else {
							$strMsg .= "Migrated KVM-Storage VM ".$kvm_server_name." from Host resource ".$kvm_server->id. " to Host resource ".$kvm_destination_host_resource_id." <br>";
							unlink($statfile);
						}
						// we now have to also adjust the vhostid in the vm resource
						$resource_fields=array();
						$resource_fields["resource_vhostid"]=$kvm_destination_host_resource_id;
						$kvm_resource->update_info($kvm_resource->id, $resource_fields);
					}
					redirect($strMsg, "tab0");
				} else {
					$strMsg ="No virtual machine selected<br>";
					redirect($strMsg, "tab0");
				}
				break;




		}
	}
}





function kvm_server_select() {

	global $OPENQRM_USER;
	global $thisfile;
	$table = new htmlobject_table_builder('appliance_id', '', '', '', 'select');

	$arHead = array();
	$arHead['appliance_state'] = array();
	$arHead['appliance_state']['title'] ='';
	$arHead['appliance_state']['sortable'] = false;

	$arHead['appliance_icon'] = array();
	$arHead['appliance_icon']['title'] ='';
	$arHead['appliance_icon']['sortable'] = false;

	$arHead['appliance_id'] = array();
	$arHead['appliance_id']['title'] ='ID';

	$arHead['appliance_name'] = array();
	$arHead['appliance_name']['title'] ='Name';

	$arHead['appliance_resource_id'] = array();
	$arHead['appliance_resource_id']['title'] ='Res.ID';
	$arHead['appliance_resource_id']['sortable'] = false;

	$arHead['appliance_resource_ip'] = array();
	$arHead['appliance_resource_ip']['title'] ='Ip';
	$arHead['appliance_resource_ip']['sortable'] = false;

	$arHead['appliance_comment'] = array();
	$arHead['appliance_comment']['title'] ='Comment';

	$kvm_server_count=0;
	$arBody = array();
	$virtualization = new virtualization();
	$virtualization->get_instance_by_type("kvm-storage");
	$kvm_server_tmp = new appliance();
	$kvm_server_array = $kvm_server_tmp->display_overview_per_virtualization($virtualization->id, $table->offset, $table->limit, $table->sort, $table->order);
	foreach ($kvm_server_array as $index => $kvm_server_db) {
		$kvm_server_resource = new resource();
		$kvm_server_resource->get_instance_by_id($kvm_server_db["appliance_resources"]);
		$resource_icon_default="/openqrm/base/img/resource.png";
		$kvm_server_icon="/openqrm/base/plugins/kvm-storage/img/plugin.png";
		$state_icon="/openqrm/base/img/$kvm_server_resource->state.png";
		if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/".$state_icon)) {
			$state_icon="/openqrm/base/img/unknown.png";
		}
		if (file_exists($_SERVER["DOCUMENT_ROOT"]."/".$kvm_server_icon)) {
			$resource_icon_default=$kvm_server_icon;
		}
		$arBody[] = array(
			'appliance_state' => "<img src=$state_icon>",
			'appliance_icon' => "<img width=24 height=24 src=$resource_icon_default>",
			'appliance_id' => $kvm_server_db["appliance_id"],
			'appliance_name' => $kvm_server_db["appliance_name"],
			'appliance_resource_id' => $kvm_server_resource->id,
			'appliance_resource_ip' => $kvm_server_resource->ip,
			'appliance_comment' => $kvm_server_db["appliance_comment"],
		);
		$kvm_server_count++;
	}
	$table->id = 'Tabelle';
	$table->css = 'htmlobject_table';
	$table->border = 1;
	$table->cellspacing = 0;
	$table->cellpadding = 3;
	$table->form_action = $thisfile;
	$table->identifier_type = "radio";
	$table->head = $arHead;
	$table->body = $arBody;
	if ($OPENQRM_USER->role == "administrator") {
		$table->bottom = array('select');
		$table->identifier = 'appliance_id';
	}
	$table->max = $kvm_server_tmp->get_count_per_virtualization($virtualization->id);

	// are there any host appliances yet ?
	if(count($arBody) > 0) {
		$disp = $table->get_string();
	} else {
		$box = new htmlobject_box();
		$box->id = 'htmlobject_box_add_host';
		$box->css = 'htmlobject_box';
		$box->label = '<br><nobr><b>No host appliances configured yet!</b></nobr>';
		$box_content = '<br><br><br><br>Please create a '.$virtualization->name.' appliance first!<br>';
		$box_content .= '<a href="/openqrm/base/server/appliance/appliance-new.php?currenttab=tab1"><b>New appliance</b></a><br>';
		$box->content = $box_content;
		$disp = $box->get_string();
	}

	// set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './tpl/' . 'kvm-storage-kvm-select.tpl.php');
	$t->setVar(array(
		'formaction' => $thisfile,
		'kvm_server_table' => $disp,
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;
}





function kvm_server_display($appliance_id) {
	global $OPENQRM_USER;
	global $thisfile;
	global $OPENQRM_SERVER_BASE_DIR;
	global $RootDir;

	$table = new htmlobject_table_identifiers_checked('kvm_server_id');

	$arHead = array();
	$arHead['kvm_server_state'] = array();
	$arHead['kvm_server_state']['title'] ='State';

	$arHead['kvm_server_icon'] = array();
	$arHead['kvm_server_icon']['title'] ='Type';

	$arHead['kvm_server_id'] = array();
	$arHead['kvm_server_id']['title'] ='ID';

	$arHead['kvm_server_name'] = array();
	$arHead['kvm_server_name']['title'] ='Name';

	$arHead['kvm_server_resource_id'] = array();
	$arHead['kvm_server_resource_id']['title'] ='Res.ID';

	$arHead['kvm_server_resource_ip'] = array();
	$arHead['kvm_server_resource_ip']['title'] ='Ip';

	$arHead['kvm_server_comment'] = array();
	$arHead['kvm_server_comment']['title'] ='';

	$arHead['kvm_server_create'] = array();
	$arHead['kvm_server_create']['title'] ='';

	$kvm_server_count=1;
	$arBody = array();
	$kvm_server_tmp = new appliance();
	$kvm_server_tmp->get_instance_by_id($appliance_id);
	$kvm_server_resource = new resource();
	$kvm_server_resource->get_instance_by_id($kvm_server_tmp->resources);
	$resource_icon_default="/openqrm/base/img/resource.png";
	$kvm_server_icon="/openqrm/base/plugins/kvm-storage/img/plugin.png";
	$state_icon="/openqrm/base/img/$kvm_server_resource->state.png";
	if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/".$state_icon)) {
		$state_icon="/openqrm/base/img/unknown.png";
	}
	if (file_exists($_SERVER["DOCUMENT_ROOT"]."/".$kvm_server_icon)) {
		$resource_icon_default=$kvm_server_icon;
	}
	$kvm_server_create_button="<a href=\"kvm-storage-vm-create.php?kvm_server_id=$kvm_server_tmp->id\" style=\"text-decoration: none\"><img height=16 width=16 src=\"/openqrm/base/plugins/aa_plugins/img/enable.png\" border=\"0\"><b> VM</b></a>";
	// here we take the resource id as the identifier because
	// we need to run commands on the resource ip
	$arBody[] = array(
		'kvm_server_state' => "<img src=$state_icon>",
		'kvm_server_icon' => "<img width=24 height=24 src=$resource_icon_default>",
		'kvm_server_id' => $kvm_server_tmp->id,
		'kvm_server_name' => $kvm_server_tmp->name,
		'kvm_server_resource_id' => $kvm_server_resource->id,
		'kvm_server_resource_ip' => $kvm_server_resource->ip,
		'kvm_server_comment' => $kvm_server_tmp->comment,
		'kvm_server_create' => $kvm_server_create_button,
	);

	$table->id = 'Tabelle';
	$table->css = 'htmlobject_table';
	$table->border = 1;
	$table->cellspacing = 0;
	$table->cellpadding = 3;
	$table->form_action = $thisfile;
	$table->sort = '';
	$table->head = $arHead;
	$table->body = $arBody;
	$table->max = $kvm_server_count;

	// table 1
	$table1 = new htmlobject_table_builder('kvm_vm_id', '', '', '', 'vms');
	$arHead1 = array();
	$arHead1['kvm_vm_state'] = array();
	$arHead1['kvm_vm_state']['title'] ='State';
	$arHead1['kvm_vm_state']['sortable'] = false;

	$arHead1['kvm_vm_id'] = array();
	$arHead1['kvm_vm_id']['title'] ='Res.';

	$arHead1['kvm_vm_name'] = array();
	$arHead1['kvm_vm_name']['title'] ='Name';

	$arHead1['kvm_vm_cpus'] = array();
	$arHead1['kvm_vm_cpus']['title'] ='CPU';

	$arHead1['kvm_vm_memory'] = array();
	$arHead1['kvm_vm_memory']['title'] ='RAM';

	$arHead1['kvm_vm_ip'] = array();
	$arHead1['kvm_vm_ip']['title'] ='IP';

	$arHead1['kvm_vm_mac'] = array();
	$arHead1['kvm_vm_mac']['title'] ='MAC';

	$arHead1['kvm_vm_actions'] = array();
	$arHead1['kvm_vm_actions']['title'] ='Actions';
	$arHead1['kvm_vm_actions']['sortable'] = false;
	$arBody1 = array();

	// check if we have a plugin implementing the remote console
	$remote_console_login_enabled=false;
	$plugin = new plugin();
	$enabled_plugins = $plugin->enabled();
	foreach ($enabled_plugins as $index => $plugin_name) {
		$plugin_remote_console_running = "$RootDir/plugins/$plugin_name/.running";
		$plugin_remote_console_hook = "$RootDir/plugins/$plugin_name/openqrm-$plugin_name-remote-console-hook.php";
		if (file_exists($plugin_remote_console_hook)) {
			if (file_exists($plugin_remote_console_running)) {
				$remote_console_login_enabled=true;
			}
		}
	}
	// prepare list of all Host resource id for the migration select
	// we need a select with the ids/ips from all resources which
	// are used by appliances with kvm capabilities
	$kvm_host_resource_list = array();
	$appliance_list = new appliance();
	$appliance_list_array = $appliance_list->get_list();
	foreach ($appliance_list_array as $index => $app) {
		$appliance_kvm_host_check = new appliance();
		$appliance_kvm_host_check->get_instance_by_id($app["value"]);
		// only active appliances
		if ((!strcmp($appliance_kvm_host_check->state, "active")) || ($appliance_kvm_host_check->resources == 0)) {
			$virtualization = new virtualization();
			$virtualization->get_instance_by_id($appliance_kvm_host_check->virtualization);
			if ((!strcmp($virtualization->type, "kvm-storage")) && (!strstr($virtualization->type, "kvm-storage-vm"))) {
				$kvm_host_resource = new resource();
				$kvm_host_resource->get_instance_by_id($appliance_kvm_host_check->resources);
				// exclude source host
				if ($kvm_host_resource->id == $kvm_server_resource->id) {
					continue;
				}
				// only active appliances
				if (!strcmp($kvm_host_resource->state, "active")) {
					$migration_select_label = "Res. ".$kvm_host_resource->id."/".$kvm_host_resource->ip;
					$kvm_host_resource_list[] = array("value"=>$kvm_host_resource->id, "label"=> $migration_select_label,);
				}
			}
		}
	}

	// prepare the vm list
	$kvm_server_vm_list_file="kvm-stat/$kvm_server_resource->id.vm_list";
	$kvm_vm_registered=array();
	$kvm_vm_count=0;
	if (file_exists($kvm_server_vm_list_file)) {
		$kvm_server_vm_list_content=file($kvm_server_vm_list_file);
		foreach ($kvm_server_vm_list_content as $index => $kvm_vm) {
			// find the vms
			if (!strstr($kvm_vm, "#")) {

				$first_at_pos = strpos($kvm_vm, "@");
				$first_at_pos++;
				$kvm_name_first_at_removed = substr($kvm_vm, $first_at_pos, strlen($kvm_vm)-$first_at_pos);
				$second_at_pos = strpos($kvm_name_first_at_removed, "@");
				$second_at_pos++;
				$kvm_name_second_at_removed = substr($kvm_name_first_at_removed, $second_at_pos, strlen($kvm_name_first_at_removed)-$second_at_pos);
				$third_at_pos = strpos($kvm_name_second_at_removed, "@");
				$third_at_pos++;
				$kvm_name_third_at_removed = substr($kvm_name_second_at_removed, $third_at_pos, strlen($kvm_name_second_at_removed)-$third_at_pos);
				$fourth_at_pos = strpos($kvm_name_third_at_removed, "@");
				$fourth_at_pos++;
				$kvm_name_fourth_at_removed = substr($kvm_name_third_at_removed, $fourth_at_pos, strlen($kvm_name_third_at_removed)-$fourth_at_pos);
				$fivth_at_pos = strpos($kvm_name_fourth_at_removed, "@");
				$fivth_at_pos++;
				$kvm_name_fivth_at_removed = substr($kvm_name_fourth_at_removed, $fivth_at_pos, strlen($kvm_name_fourth_at_removed)-$fivth_at_pos);
				$sixth_at_pos = strpos($kvm_name_fivth_at_removed, "@");
				$sixth_at_pos++;
				$kvm_name_sixth_at_removed = substr($kvm_name_fivth_at_removed, $sixth_at_pos, strlen($kvm_name_fivth_at_removed)-$sixth_at_pos);
				$seventh_at_pos = strpos($kvm_name_sixth_at_removed, "@");
				$seventh_at_pos++;

				$kvm_vm_state = trim(substr($kvm_vm, 0, $first_at_pos-1));
				$kvm_short_name = trim(substr($kvm_name_first_at_removed, 0, $second_at_pos-1));
				$kvm_vm_mac = trim(substr($kvm_name_second_at_removed, 0, $third_at_pos-1));
				$kvm_vm_cpus = trim(substr($kvm_name_third_at_removed, 0, $fourth_at_pos-1));
				$kvm_vm_memory = trim(substr($kvm_name_fourth_at_removed, 0, $fivth_at_pos-1));
				$kvm_vm_vnc = trim(substr($kvm_name_fivth_at_removed, 0, $sixth_at_pos-1));
				// get ip
				$kvm_resource = new resource();
				$kvm_resource->get_instance_by_mac($kvm_vm_mac);
				$kvm_vm_ip = $kvm_resource->ip;
				$kvm_vm_id = $kvm_resource->id;

				// fill the actions and set state icon
				$vm_actions = "";
				$mig_selected = array();
				if (!strcmp($kvm_vm_state, "1")) {
					$state_icon="/openqrm/base/img/active.png";
					$vm_actions = "<nobr><a href=\"$thisfile?identifier[]=$kvm_short_name&action=stop&kvm_server_id=$kvm_server_tmp->id&kvm_vm_mac_ar[$kvm_short_name]=$kvm_vm_mac&kvm_vm_vnc[$kvm_short_name]=$kvm_vm_vnc\" style=\"text-decoration:none;\"><img height=20 width=20 src=\"/openqrm/base/plugins/aa_plugins/img/stop.png\" border=\"0\"> Stop</a>&nbsp;&nbsp;&nbsp;&nbsp;";
					$vm_actions .= "<a href=\"$thisfile?identifier[]=$kvm_short_name&action=restart&kvm_server_id=$kvm_server_tmp->id&kvm_vm_mac_ar[$kvm_short_name]=$kvm_vm_mac\" style=\"text-decoration:none;\"><img height=16 width=16 src=\"/openqrm/base/img/active.png\" border=\"0\"> Restart</a>";
					// show only if not idle
					if ($kvm_resource->imageid != 1) {
						// remote consle enabled ?
						if ($remote_console_login_enabled) {
							$vm_actions .= "<a href=\"$thisfile?identifier[]=$kvm_short_name&action=console&kvm_server_id=$kvm_server_tmp->id&kvm_vm_mac_ar[$kvm_short_name]=$kvm_vm_mac&kvm_vm_vnc[$kvm_short_name]=$kvm_vm_vnc\" style=\"text-decoration:none;\"><img height=16 width=16 src=\"img/login.png\" border=\"0\"> Console</a>";
							// migration
							$migration_select = kvm_htmlobject_select("kvm_migrate_to_id[$kvm_short_name]", $kvm_host_resource_list, 'Migrate', $mig_selected);
							$vm_actions .= "<br>Migrate to : ".$migration_select;
							// end nobr
						}
					}
					$vm_actions .= "</nobr>";

				} else {
					$state_icon="/openqrm/base/img/off.png";
					$vm_actions = "<nobr><a href=\"$thisfile?identifier[]=$kvm_short_name&action=start&kvm_server_id=$kvm_server_tmp->id\" style=\"text-decoration:none;\"><img height=20 width=20 src=\"/openqrm/base/plugins/aa_plugins/img/start.png\" border=\"0\"> Start</a>&nbsp;&nbsp;&nbsp;&nbsp;";
					$vm_actions .= "<a href=\"kvm-storage-vm-config.php?kvm_server_name=$kvm_short_name&kvm_server_id=$kvm_server_tmp->id\" style=\"text-decoration:none;\"><img height=16 width=16 src=\"/openqrm/base/plugins/aa_plugins/img/plugin.png\" border=\"0\"> Config</a>&nbsp;&nbsp;&nbsp;&nbsp;";
					$vm_actions .= "<a href=\"$thisfile?identifier[]=$kvm_short_name&action=delete&kvm_server_id=$kvm_server_tmp->id&kvm_vm_mac_ar[$kvm_short_name]=$kvm_vm_mac\" style=\"text-decoration:none;\"><img height=20 width=20 src=\"/openqrm/base/plugins/aa_plugins/img/disable.png\" border=\"0\"> Delete</a></nobr>";
				}
				// here we check if the VM is really running on this Host
				// In case of a shared SAN backend e.g. a migrated VM config will be available for all host
				// -> do not show migrated VMs
				if ($kvm_resource->vhostid != $kvm_server_resource->id) {
					continue;
				}
				// otherwise put it in the list
				$kvm_vm_registered[] = $kvm_short_name;
				$kvm_vm_count++;

				$arBody1[] = array(
					'kvm_vm_state' => "<img src=$state_icon><input type='hidden' name='kvm_vm_mac_ar[$kvm_short_name]' value=$kvm_vm_mac><input type='hidden' name='kvm_vm_vnc[$kvm_short_name]' value=$kvm_vm_vnc>",
					'kvm_vm_id' => $kvm_vm_id,
					'kvm_vm_name' => $kvm_short_name,
					'kvm_vm_cpus' => $kvm_vm_cpus,
					'kvm_vm_memory' => $kvm_vm_memory." MB",
					'kvm_vm_ip' => $kvm_vm_ip."<br><small>(vnc:".$kvm_vm_vnc.")</small>",
					'kvm_vm_mac' => $kvm_vm_mac,
					'kvm_vm_actions' => $vm_actions,
				);

			}
		}
	}
	$table1->add_headrow("<input type='hidden' name='kvm_server_id' value=$appliance_id>");
	$table1->id = 'Tabelle';
	$table1->css = 'htmlobject_table';
	$table1->border = 1;
	$table1->cellspacing = 0;
	$table1->cellpadding = 3;
	$table1->form_action = $thisfile;
	$table1->autosort = true;
	$table1->identifier_type = "checkbox";
	$table1->head = $arHead1;
	$table1->body = $arBody1;
	if ($OPENQRM_USER->role == "administrator") {
		if ($remote_console_login_enabled) {
			$table1->bottom = array('start', 'stop', 'restart', 'console', 'delete', 'migrate', 'reload');
		} else {
			$table1->bottom = array('start', 'stop', 'restart', 'delete', 'migrate', 'reload');
		}
		$table1->identifier = 'kvm_vm_name';
	}
	$table1->max = $kvm_vm_count;

	// set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './tpl/' . 'kvm-storage-vms.tpl.php');
	$t->setVar(array(
		'formaction' => $thisfile,
		'kvm_server_table' => $table->get_string(),
		'kvm_server_id' => $kvm_server_resource->id,
		'kvm_server_name' => $kvm_server_resource->hostname,
		'kvm_vm_table' => $table1->get_string(),
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;
}




$output = array();
if(htmlobject_request('action') != '') {
	if (isset($_REQUEST['identifier'])) {
		switch (htmlobject_request('action')) {
			case 'select':
				foreach($_REQUEST['identifier'] as $id) {
					$output[] = array('label' => 'KVM Storage VM Manager', 'value' => kvm_server_display($id));
				}
				break;
			case 'reload':
				foreach($_REQUEST['identifier'] as $id) {
					$output[] = array('label' => 'KVM Storage VM Manager', 'value' => kvm_server_display($id));
				}
				break;
		}
	} else {
		$output[] = array('label' => 'KVM Storage VM Manager', 'value' => kvm_server_select());
	}
} else if (strlen($kvm_server_id)) {
	$output[] = array('label' => 'KVM Storage VM Manager', 'value' => kvm_server_display($kvm_server_id));
} else  {
	$output[] = array('label' => 'KVM Storage VM Manager', 'value' => kvm_server_select());
}


?>
<script type="text/javascript">
	$("#progressbar").remove();
</script>
<?php

echo htmlobject_tabmenu($output);

?>
