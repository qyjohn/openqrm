<!doctype html>
<html lang="en">
<head>
	<title>Xen Manager</title>
	<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
	<link rel="stylesheet" type="text/css" href="xen-storage.css" />
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
global $OPENQRM_SERVER_BASE_DIR;
$refresh_delay=1;
$refresh_loop_max=20;
$refresh_migrate_loop_max=360;

$xen_id = htmlobject_request('xen_id');
$xen_migrate_to_id = htmlobject_request('xen_migrate_to_id');
$xen_migrate_type = htmlobject_request('xen_migrate_type');
$xen_vm_mac = htmlobject_request('xen_vm_mac');
$xen_vm_mac_ar = htmlobject_request('xen_vm_mac_ar');
global $xen_id;
global $xen_migrate_to_id;
global $xen_migrate_type;
global $xen_vm_mac;
global $xen_vm_mac_ar;

$event = new event();
global $event;
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $OPENQRM_SERVER_BASE_DIR;



function xen_htmlobject_select($name, $value, $title = '', $selected = '') {
	$html = new htmlobject_select();
	$html->name = $name;
	$html->title = $title;
	$html->selected = $selected;
	$html->text_index = array("value" => "value", "text" => "label");
	$html->text = $value;
	return $html->get_string();
}



function redirect($strMsg, $currenttab = 'tab0', $url = '') {
	global $thisfile;
	global $xen_id;
	if($url == '') {
		$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab='.$currenttab.'&xen_id='.$xen_id;
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




// Dom0 actions
$strMsg = '';
if(htmlobject_request('action') != '') {
	if ($OPENQRM_USER->role == "administrator") {

		switch (htmlobject_request('action')) {

			case 'select':
				if (isset($_REQUEST['identifier'])) {
					foreach($_REQUEST['identifier'] as $xen_id) {
						show_progressbar();
						$xen_appliance = new appliance();
						$xen_appliance->get_instance_by_id($xen_id);
						$xen = new resource();
						$xen->get_instance_by_id($xen_appliance->resources);
						// remove current stat file
						$statfile="xen-stat/$xen->id.vm_list";
						if (file_exists($statfile)) {
							unlink($statfile);
						}
						// send command
						$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/xen-storage/bin/openqrm-xen-storage-vm post_vm_list -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
						$xen->send_command($xen->ip, $resource_command);
						// wait for statfile to appear again
						if (!wait_for_statfile($statfile, false)) {
							$strMsg .= "Error while refreshing Xen vm list ! Please check the Event-Log<br>";
						} else {
							$strMsg .= "Refreshed Xen vm list<br>";
						}
						redirect($strMsg, "tab0");
					}
				}
				break;
		}
	}
}


// xen vm actions
if(htmlobject_request('action_table1') != '') {
	switch (htmlobject_request('action_table1')) {

		case 'start':
			if (isset($_REQUEST['identifier_table1'])) {
				show_progressbar();
				foreach($_REQUEST['identifier_table1'] as $xen_name) {
					$xen_appliance = new appliance();
					$xen_appliance->get_instance_by_id($xen_id);
					$xen = new resource();
					$xen->get_instance_by_id($xen_appliance->resources);
					// remove current stat file
					$statfile="xen-stat/$xen->id.vm_list";
					if (file_exists($statfile)) {
						unlink($statfile);
					}
					// send command
					$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/xen-storage/bin/openqrm-xen-storage-vm start -n $xen_name -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
					$xen->send_command($xen->ip, $resource_command);
					// wait for statfile to appear again
					if (!wait_for_statfile($statfile, false)) {
						$strMsg .= "Error while starting Xen vm $xen_name ! Please check the Event-Log<br>";
					} else {
						$strMsg .= "Started Xen vm $xen_name<br>";
					}
				}
				redirect($strMsg, "tab0");
			}
			break;

		case 'stop':
			if (isset($_REQUEST['identifier_table1'])) {
				show_progressbar();
				foreach($_REQUEST['identifier_table1'] as $xen_name) {
					$xen_appliance = new appliance();
					$xen_appliance->get_instance_by_id($xen_id);
					$xen = new resource();
					$xen->get_instance_by_id($xen_appliance->resources);
					// get the vnc parameter per vm
					$xen_vm_vnc_config_arr = htmlobject_request('xen_vm_vnc');
					$xen_vnc_vm_port = $xen_vm_vnc_config_arr[$xen_name];
					// get the vm resource
					$xen_vm_mac = $xen_vm_mac_ar[$xen_name];
					$xen_resource = new resource();
					$xen_resource->get_instance_by_mac($xen_vm_mac);
					$xen_vm_id=$xen_resource->id;

					// before we stop the vm we provide a hook for the remote-console plugins to stop the vm console
					// check if we have a plugin implementing the remote console
					$plugin = new plugin();
					$enabled_plugins = $plugin->enabled();
					foreach ($enabled_plugins as $index => $plugin_name) {
						$plugin_remote_console_running = "$RootDir/plugins/$plugin_name/.running";
						$plugin_remote_console_hook = "$RootDir/plugins/$plugin_name/openqrm-$plugin_name-remote-console-hook.php";
						if (file_exists($plugin_remote_console_hook)) {
							if (file_exists($plugin_remote_console_running)) {
								$event->log("console", $_SERVER['REQUEST_TIME'], 5, "xen-manager.php", "Found plugin $plugin_name providing a remote console/ $xen->ip, $xen_vnc_vm_port, $xen_vm_id, $xen_vm_mac, $xen_name.", "", "", 0, 0, $xen_resource->id);
								require_once "$plugin_remote_console_hook";
								$plugin_remote_console_function="openqrm_"."$plugin_name"."_disable_remote_console";
								$plugin_remote_console_function=str_replace("-", "_", $plugin_remote_console_function);
								$plugin_remote_console_function($xen->ip, $xen_vnc_vm_port, $xen_vm_id, $xen_vm_mac, $xen_name);
								$strMsg .="Stopping the remote console to $xen_name on Host $xen->ip<br>";
							}
						}
					}

					// prepare the vm command
					// remove current stat file
					$statfile="xen-stat/$xen->id.vm_list";
					if (file_exists($statfile)) {
						unlink($statfile);
					}
					// send command
					$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/xen-storage/bin/openqrm-xen-storage-vm stop -n $xen_name -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
					$xen->send_command($xen->ip, $resource_command);
					// wait for statfile to appear again
					if (!wait_for_statfile($statfile, false)) {
						$strMsg .= "Error while stopping Xen vm $xen_name ! Please check the Event-Log<br>";
					} else {
						$strMsg .= "Stopped Xen vm $xen_name<br>";
					}
				}
				redirect($strMsg, "tab0");
			}
			break;

		case 'reboot':
			if (isset($_REQUEST['identifier_table1'])) {
				show_progressbar();
				foreach($_REQUEST['identifier_table1'] as $xen_name) {
					$xen_appliance = new appliance();
					$xen_appliance->get_instance_by_id($xen_id);
					$xen = new resource();
					$xen->get_instance_by_id($xen_appliance->resources);
					// remove current stat file
					$statfile="xen-stat/$xen->id.vm_list";
					if (file_exists($statfile)) {
						unlink($statfile);
					}
					// send command
					$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/xen-storage/bin/openqrm-xen-storage-vm reboot -n $xen_name -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
					$xen->send_command($xen->ip, $resource_command);
					// wait for statfile to appear again
					if (!wait_for_statfile($statfile, false)) {
						$strMsg .= "Error while rebooting Xen vm $xen_name ! Please check the Event-Log<br>";
					} else {
						$strMsg .= "Rebooted Xen vm $xen_name<br>";
					}
				}
				redirect($strMsg, "tab0");
			}
			break;

		case 'remove':
			if (isset($_REQUEST['identifier_table1'])) {
				show_progressbar();
				foreach($_REQUEST['identifier_table1'] as $xen_name) {
					// check if the resource still belongs to an appliance, if yes we do not remove it
					$xen_vm_mac = $xen_vm_mac_ar[$xen_name];
					$xen_resource = new resource();
					$xen_resource->get_instance_by_mac($xen_vm_mac);
					$xen_vm_id=$xen_resource->id;
					$resource_is_used_by_appliance = "";
					$remove_error = 0;
					$appliance = new appliance();
					$appliance_id_list = $appliance->get_all_ids();
					foreach($appliance_id_list as $appliance_list) {
						$appliance_id = $appliance_list['appliance_id'];
						$app_resource_remove_check = new appliance();
						$app_resource_remove_check->get_instance_by_id($appliance_id);
						if ($app_resource_remove_check->resources == $xen_vm_id) {
							$resource_is_used_by_appliance .= $appliance_id." ";
							$remove_error = 1;
						}
					}
					if ($remove_error == 1) {
						$strMsg .= "VM Resource id ".$xen_vm_id." is used by appliance(s): ".$resource_is_used_by_appliance." <br>";
						$strMsg .= "Not removing VM resource id ".$xen_vm_id." !<br>";
						continue;
					}
					// remove vm
					$xen_appliance = new appliance();
					$xen_appliance->get_instance_by_id($xen_id);
					$xen = new resource();
					$xen->get_instance_by_id($xen_appliance->resources);
					// remove current stat file
					$statfile="xen-stat/$xen->id.vm_list";
					if (file_exists($statfile)) {
						unlink($statfile);
					}
					// send command
					$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/xen-storage/bin/openqrm-xen-storage-vm remove -n $xen_name -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
					$xen->send_command($xen->ip, $resource_command);
					// we should remove the resource of the vm !
					$xen_resource->remove($xen_vm_id, $xen_vm_mac);
					// wait for statfile to appear again
					if (!wait_for_statfile($statfile, false)) {
						$strMsg .= "Error while removing Xen vm $xen_name ! Please check the Event-Log<br>";
					} else {
						$strMsg .= "Removed Xen vm $xen_name<br>";
					}
				}
				redirect($strMsg, "tab0");
			}
			break;

		case 'migrate':
			if (isset($_REQUEST['identifier_table1'])) {
				show_progressbar();
				foreach($_REQUEST['identifier_table1'] as $xen_name) {
					# get vm
					$xen_vm_mac = $xen_vm_mac_ar[$xen_name];
					$xen_vm = new resource();
					$xen_vm->get_instance_by_mac($xen_vm_mac);
					# get host
					$xen_appliance = new appliance();
					$xen_appliance->get_instance_by_id($xen_id);
					$xen = new resource();
					$xen->get_instance_by_id($xen_appliance->resources);
					# get destination
					$destination = new resource();
					$destination->get_instance_by_id($xen_migrate_to_id);
                                        // create the /etc/xen/vm.cfg on the destination
                                        $destination_command = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/xen-storage/bin/openqrm-xen-storage-vm create_vm_config -n ".$xen_name;
                                        $destination->send_command($destination->ip, $destination_command);
                                        // give some time
                                        sleep(4);
					// remove current stat file
					$statfile="xen-stat/".$xen_name.".vm_migrated_successfully";
					if (file_exists($statfile)) {
						unlink($statfile);
					}
					// send command
                                        if ($xen_migrate_type == "1") {
                                                $xen_migrate_parameter = "-t live";
                                        } else {
                                                $xen_migrate_parameter = "-t regular";
                                        }
                                        $resource_command=$OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/xen-storage/bin/openqrm-xen-storage-vm migrate -n ".$xen_name." -i ".$destination->ip." ".$xen_migrate_parameter." -u ".$OPENQRM_ADMIN->name." -p ".$OPENQRM_ADMIN->password;
					$xen->send_command($xen->ip, $resource_command);
					// wait for statfile to appear again
					if (!wait_for_statfile($statfile, true)) {
						$strMsg .= "Error while migrating Xen vm ".$xen_name."! Please check the Event-Log<br>";
                                                // remove config created on the destionation
                                                $destination_command = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/xen-storage/bin/openqrm-xen-storage-vm remove_vm_config -n ".$xen_name;
                                                $destination->send_command($destination->ip, $destination_command);
						continue;
					} else {
						$strMsg .= "Migrated Xen vm $xen_name to Host resource ID ".$xen_migrate_to_id."<br>";
                                                // restart the xen-storage monitord for VM
                                                $destination_command = $OPENQRM_SERVER_BASE_DIR."/openqrm/plugins/xen-storage/bin/openqrm-xen-storage-vm restart_vm_client -n ".$xen_name;
                                                $destination->send_command($destination->ip, $destination_command);
						unlink($statfile);
					}
					// we now have to also adjust the vhostid in the vm resource
					$resource_fields=array();
					$resource_fields["resource_vhostid"]=$xen_migrate_to_id;
					$xen_vm->update_info($xen_vm->id, $resource_fields);
				}
				redirect($strMsg, "tab0");
			}
			break;

		case 'reload':
			if (strlen($xen_id)) {
				show_progressbar();
				$xen_appliance = new appliance();
				$xen_appliance->get_instance_by_id($xen_id);
				$xen = new resource();
				$xen->get_instance_by_id($xen_appliance->resources);
				// remove current stat file
				$statfile="xen-stat/$xen->id.vm_list";
				if (file_exists($statfile)) {
					unlink($statfile);
				}
				// send command
				$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/xen-storage/bin/openqrm-xen-storage-vm post_vm_list -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
				$xen->send_command($xen->ip, $resource_command);
				// wait for statfile to appear again
				if (!wait_for_statfile($statfile, false)) {
					$strMsg .= "Error while refreshing Xen vm list ! Please check the Event-Log<br>";
				} else {
					$strMsg .= "Refreshed Xen vm list<br>";
				}
				redirect($strMsg, "tab0");
			}
			break;


		case 'console':
			if (isset($_REQUEST['identifier_table1'])) {
				foreach($_REQUEST['identifier_table1'] as $xen_server_name) {
					show_progressbar();
					// get the vnc parameter per vm
					$xen_vm_vnc_config_arr = htmlobject_request('xen_vm_vnc');
					$xen_vnc_vm_port = $xen_vm_vnc_config_arr[$xen_server_name];
					// get the resource
					$xen_vm_mac = $xen_vm_mac_ar[$xen_server_name];
					$xen_resource = new resource();
					$xen_resource->get_instance_by_mac($xen_vm_mac);
					$xen_vm_id=$xen_resource->id;

					// get the host
					$xen_appliance = new appliance();
					$xen_appliance->get_instance_by_id($xen_id);
					$xen = new resource();
					$xen->get_instance_by_id($xen_appliance->resources);

					// check if we have a plugin implementing the remote console
					$plugin = new plugin();
					$enabled_plugins = $plugin->enabled();
					foreach ($enabled_plugins as $index => $plugin_name) {
						$plugin_remote_console_running = "$RootDir/plugins/$plugin_name/.running";
						$plugin_remote_console_hook = "$RootDir/plugins/$plugin_name/openqrm-$plugin_name-remote-console-hook.php";
						if (file_exists($plugin_remote_console_hook)) {
							if (file_exists($plugin_remote_console_running)) {
								$event->log("console", $_SERVER['REQUEST_TIME'], 5, "xen-manager.php", "Found plugin $plugin_name providing a remote console.", "", "", 0, 0, $resource->id);
								require_once "$plugin_remote_console_hook";
								$plugin_remote_console_function="openqrm_"."$plugin_name"."_remote_console";
								$plugin_remote_console_function=str_replace("-", "_", $plugin_remote_console_function);
								$plugin_remote_console_function($xen->ip, $xen_vnc_vm_port, $xen_vm_id, $xen_vm_mac, $xen_server_name);
								$strMsg .="Opening a remote console to $xen_server_name on $xen->ip : $xen_vnc_vm_port<br>";
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

	}
}






function xen_select() {
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

	$xen_count=0;
	$arBody = array();
	$virtualization = new virtualization();
	$virtualization->get_instance_by_type("xen-storage");
	$xen_tmp = new appliance();
	$xen_array = $xen_tmp->display_overview_per_virtualization($virtualization->id, $table->offset, $table->limit, $table->sort, $table->order);
	foreach ($xen_array as $index => $xen_db) {
		$xen_resource = new resource();
		$xen_resource->get_instance_by_id($xen_db["appliance_resources"]);
		$xen_count++;
		$resource_icon_default="/openqrm/base/img/resource.png";
		$xen_icon="/openqrm/base/plugins/xen/img/plugin.png";
		$state_icon="/openqrm/base/img/$xen_resource->state.png";
		if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/".$state_icon)) {
			$state_icon="/openqrm/base/img/unknown.png";
		}
		if (file_exists($_SERVER["DOCUMENT_ROOT"]."/".$xen_icon)) {
			$resource_icon_default=$xen_icon;
		}
		$arBody[] = array(
			'appliance_state' => "<img width=16 height=16 src=$state_icon>",
			'appliance_icon' => "<img width=24 height=24 src=$resource_icon_default>",
			'appliance_id' => $xen_db["appliance_id"],
			'appliance_name' => $xen_resource->hostname,
			'appliance_resource_id' => $xen_resource->id,
			'appliance_resource_ip' => $xen_resource->ip,
			'appliance_comment' => $xen_resource->capabilities,
		);
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
	$table->max = $xen_tmp->get_count_per_virtualization($virtualization->id);

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
	$t->setFile('tplfile', './tpl/' . 'xen-storage-xen-select.tpl.php');
	$t->setVar(array(
		'formaction' => $thisfile,
		'xen_server_table' => $disp,
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;


}




function xen_display($appliance_id) {
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_USER;
	global $thisfile;
	global $RootDir;

	$xen_appliance = new appliance();
	$xen_appliance->get_instance_by_id($appliance_id);
	$xen = new resource();
	$xen->get_instance_by_id($xen_appliance->resources);

	// dom0 infos
	$arBody = array();
	$resource_icon_default="/openqrm/base/img/resource.png";
	$xen_icon="/openqrm/base/plugins/xen/img/plugin.png";
	$state_icon="/openqrm/base/img/$xen->state.png";
	if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/".$state_icon)) {
		$state_icon="/openqrm/base/img/unknown.png";
	}
	if (file_exists($_SERVER["DOCUMENT_ROOT"]."/".$xen_icon)) {
		$resource_icon_default=$xen_icon;
	}
	$xen_create_button="<a href=\"xen-storage-vm-create.php?xen_id=$xen_appliance->id\" style=\"text-decoration: none\"><img height=20 width=20 src=\"/openqrm/base/plugins/aa_plugins/img/enable.png\" border=\"0\"><b> VM</b></a>";
	// here we take the resource id as the identifier because
	// we need to run commands on the resource ip
	$arBody[] = array(
		'xen_state' => "<img width=16 height=16 src=$state_icon><input type='hidden' name='xen_id' value=$appliance_id>",
		'xen_icon' => "<img width=24 height=24 src=$resource_icon_default>",
		'xen_id' => $xen_appliance->id,
		'xen_name' => $xen->hostname,
		'xen_resource_id' => $xen->id,
		'xen_resource_ip' => $xen->ip,
		'xen_resource_memory' => $xen->memtotal." MB",
		'xen_create' => $xen_create_button,
	);


	// vm infos
	$loop = 0;
	$xen_vm_count=0;
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

	$xen_vm_list_file="xen-stat/$xen->id.vm_list";
	if (file_exists($xen_vm_list_file)) {
		$xen_vm_list_content=file($xen_vm_list_file);
		foreach ($xen_vm_list_content as $index => $xenxmoutput) {
			if ($loop == 0) {
				$loop = 1;
				continue;
			}
			$first_at_pos = strpos($xenxmoutput, "@");
			$first_at_pos++;
			$xen_name_first_at_removed = substr($xenxmoutput, $first_at_pos, strlen($xenxmoutput)-$first_at_pos);
			$second_at_pos = strpos($xen_name_first_at_removed, "@");
			$second_at_pos++;
			$xen_name_second_at_removed = substr($xen_name_first_at_removed, $second_at_pos, strlen($xen_name_first_at_removed)-$second_at_pos);
			$third_at_pos = strpos($xen_name_second_at_removed, "@");
			$third_at_pos++;
			$xen_name_third_at_removed = substr($xen_name_second_at_removed, $third_at_pos, strlen($xen_name_second_at_removed)-$third_at_pos);
			$fourth_at_pos = strpos($xen_name_third_at_removed, "@");
			$fourth_at_pos++;
			$xen_name_fourth_at_removed = substr($xen_name_third_at_removed, $fourth_at_pos, strlen($xen_name_third_at_removed)-$fourth_at_pos);
			$fivth_at_pos = strpos($xen_name_fourth_at_removed, "@");
			$fivth_at_pos++;
			$xen_name_fivth_at_removed = substr($xen_name_fourth_at_removed, $fivth_at_pos, strlen($xen_name_fourth_at_removed)-$fivth_at_pos);
			$sixth_at_pos = strpos($xen_name_fivth_at_removed, "@");
			$sixth_at_pos++;
			$xen_name_sixth_at_removed = substr($xen_name_fivth_at_removed, $sixth_at_pos, strlen($xen_name_fivth_at_removed)-$sixth_at_pos);
			$seventh_at_pos = strpos($xen_name_sixth_at_removed, "@");
			$seventh_at_pos++;

			$xen_openqrm_vm = trim(substr($xenxmoutput, 0, $first_at_pos-1));
			$xen_name = trim(substr($xen_name_first_at_removed, 0, $second_at_pos-1));
			$xen_vm_memory = trim(substr($xen_name_second_at_removed, 0, $third_at_pos-1));
			$xen_vm_mac = trim(substr($xen_name_third_at_removed, 0, $fourth_at_pos-1));
			$xen_vm_bridge = trim(substr($xen_name_fourth_at_removed, 0, $fivth_at_pos-1));
			$xen_vm_vnc = trim(substr($xen_name_fivth_at_removed, 0, $sixth_at_pos-1));
			$xen_vm_online = trim(substr($xen_name_sixth_at_removed, 0));

			$xen_vm_resource = new resource();
			$xen_vm_resource->get_instance_by_mac($xen_vm_mac);
			$xen_vm_id = $xen_vm_resource->id;
			$xen_vm_ip = $xen_vm_resource->ip;

			// if it is an openqrm vm -> plus migration
			// we need a select with the ids/ips from all resources which
			// are used by appliances with xen capabilities
			$xen_host_resource_list = array();
			$appliance_list = new appliance();
			$appliance_list_array = $appliance_list->get_list();
			foreach ($appliance_list_array as $index => $app) {
				$appliance_xen_host_check = new appliance();
				$appliance_xen_host_check->get_instance_by_id($app["value"]);
				$virtualization = new virtualization();
				$virtualization->get_instance_by_id($appliance_xen_host_check->virtualization);
				if ((!strcmp($virtualization->type, "xen-storage")) && (!strstr($virtualization->type, "xen-storage-vm"))) {
					$xen_host_resource = new resource();
					$xen_host_resource->get_instance_by_id($appliance_xen_host_check->resources);
					// exclude source host
					if ($xen_host_resource->id == $xen->id) {
						continue;
					}
					// only active appliances
					if (!strcmp($xen_host_resource->state, "active")) {
						$xen_host_resource_list[] = array("value"=>$xen_host_resource->id, "label"=>$xen_host_resource->ip,);
					}
				}
			}

			$migrateion_select = xen_htmlobject_select('xen_migrate_to_id', $xen_host_resource_list, '', $xen_host_resource_list);

			// here we fill table 1
			$xen_vm_actions = "";
			$xen_vm_migrate_actions = "";
			// online ? openqrm-vm ?
			if ($xen_vm_online == 1) {
				$xen_vm_state_icon = "/openqrm/base/img/active.png";
				// online actions
				$xen_vm_actions= $xen_vm_actions."<nobr><a href=\"$thisfile?identifier_table1[]=$xen_name&action_table1=stop&xen_id=$xen_appliance->id&xen_vm_mac_ar[$xen_name]=$xen_vm_mac&xen_vm_vnc[$xen_name]=$xen_vm_vnc\" style=\"text-decoration:none;\"><img height=20 width=20 src=\"/openqrm/base/plugins/aa_plugins/img/stop.png\" border=\"0\"> Stop</a>&nbsp;";
				$xen_vm_actions = $xen_vm_actions."<a href=\"$thisfile?identifier_table1[]=$xen_name&action_table1=reboot&xen_id=$xen_appliance->id&xen_vm_mac_ar[$xen_name]=$xen_vm_mac\" style=\"text-decoration:none;\"><img height=16 width=16 src=\"/openqrm/base/img/active.png\" border=\"0\"> Restart</a>&nbsp;";
				// remote consle enabled ?
				if ($remote_console_login_enabled) {
					$xen_vm_actions .= "<a href=\"$thisfile?identifier_table1[]=$xen_name&action_table1=console&xen_id=$xen_appliance->id&xen_vm_mac_ar[$xen_name]=$xen_vm_mac&xen_vm_vnc[$xen_name]=$xen_vm_vnc\" style=\"text-decoration:none;\"><img height=16 width=16 src=\"img/login.png\" border=\"0\"> Console</a>";
				}
				$xen_vm_actions .= "</nobr>";

				// migration actions
				if ($xen_openqrm_vm == 1) {
					$xen_vm_migrate_actions = $xen_vm_migrate_actions."<b><input type='checkbox' name='xen_migrate_type' value='1'> live</b>";
					$xen_vm_migrate_actions = $xen_vm_migrate_actions.$migrateion_select;
				}
			} else {
				$xen_vm_state_icon = "/openqrm/base/img/off.png";
				$xen_vm_actions= $xen_vm_actions."<nobr><a href=\"$thisfile?identifier_table1[]=$xen_name&action_table1=start&xen_id=$xen_appliance->id\" style=\"text-decoration:none;\"><img height=20 width=20 src=\"/openqrm/base/plugins/aa_plugins/img/start.png\" border=\"0\"> Start</a>&nbsp;";
				if ($xen_openqrm_vm == 1) {
					$xen_vm_actions = $xen_vm_actions."<a href=\"xen-storage-vm-config.php?xen_name=$xen_name&xen_id=$xen_appliance->id\" style=\"text-decoration:none;\"><img height=20 width=20 src=\"/openqrm/base/plugins/aa_plugins/img/plugin.png\" border=\"0\"> Config</a>&nbsp;";
					$xen_vm_actions = $xen_vm_actions."<a href=\"$thisfile?identifier_table1[]=$xen_name&action_table1=remove&xen_id=$xen_appliance->id&xen_vm_mac_ar[$xen_name]=$xen_vm_mac\" style=\"text-decoration:none;\"><img height=16 width=16 src=\"/openqrm/base/img/off.png\" border=\"0\"> Remove</a>&nbsp;";
				}
				$xen_vm_actions .= "</nobr>";
			}

			// add to table1
			$arBody1[] = array(
				'xen_vm_state' => "<img src=$xen_vm_state_icon><input type='hidden' name='xen_vm_mac_ar[$xen_name]' value=$xen_vm_mac><input type='hidden' name='xen_vm_vnc[$xen_name]' value=$xen_vm_vnc>",
				'xen_vm_id' => $xen_vm_id,
				'xen_vm_name' => $xen_name,
				'xen_vm_vnc' => $xen_vm_vnc,
				'xen_vm_ip' => $xen_vm_ip,
				'xen_vm_mac' => $xen_vm_mac,
				'xen_vm_bridge' => $xen_vm_bridge,
				'xen_vm_memory' => $xen_vm_memory." MB",
				'xen_vm_actions' => $xen_vm_actions,
				'xen_vm_migrate_actions' => $xen_vm_migrate_actions,
			);
			$xen_vm_count++;


		}
	}


	// main output section
	// ############################ Xen Host table #############################

	$table = new htmlobject_table_identifiers_checked('xen_id');

	$arHead = array();
	$arHead['xen_state'] = array();
	$arHead['xen_state']['title'] ='';

	$arHead['xen_icon'] = array();
	$arHead['xen_icon']['title'] ='';

	$arHead['xen_id'] = array();
	$arHead['xen_id']['title'] ='ID';

	$arHead['xen_name'] = array();
	$arHead['xen_name']['title'] ='Name';

	$arHead['xen_resource_id'] = array();
	$arHead['xen_resource_id']['title'] ='Res.ID';

	$arHead['xen_resource_ip'] = array();
	$arHead['xen_resource_ip']['title'] ='Ip';

	$arHead['xen_resource_memory'] = array();
	$arHead['xen_resource_memory']['title'] ='Memory';

	$arHead['xen_create'] = array();
	$arHead['xen_create']['title'] ='';

	$table->id = 'Tabelle';
	$table->css = 'htmlobject_table';
	$table->border = 1;
	$table->cellspacing = 0;
	$table->cellpadding = 3;
	$table->form_action = $thisfile;
	$table->sort = '';
	$table->head = $arHead;
	$table->body = $arBody;
	$table->max = 1;


	// ############################ Xen vms table ###################

	$table1 = new htmlobject_table_builder('xen_vm_name', '', '', '', 'vms');

	$arHead1 = array();
	$arHead1['xen_vm_state'] = array();
	$arHead1['xen_vm_state']['title'] ='';
	$arHead1['xen_vm_state']['sortable'] = false;

	$arHead1['xen_vm_id'] = array();
	$arHead1['xen_vm_id']['title'] ='Res.';

	$arHead1['xen_vm_name'] = array();
	$arHead1['xen_vm_name']['title'] ='Name';

	$arHead1['xen_vm_vnc'] = array();
	$arHead1['xen_vm_vnc']['title'] ='vnc';

	$arHead1['xen_vm_ip'] = array();
	$arHead1['xen_vm_ip']['title'] ='IP';

	$arHead1['xen_vm_mac'] = array();
	$arHead1['xen_vm_mac']['title'] ='MAC';

	$arHead1['xen_vm_bridge'] = array();
	$arHead1['xen_vm_bridge']['title'] ='Bridge';

	$arHead1['xen_vm_memory'] = array();
	$arHead1['xen_vm_memory']['title'] ='Memory';

	$arHead1['xen_vm_actions'] = array();
	$arHead1['xen_vm_actions']['title'] ='VM-Actions';
	$arHead1['xen_vm_actions']['sortable'] = false;

	$arHead1['$xen_vm_migrate_actions'] = array();
	$arHead1['$xen_vm_migrate_actions']['title'] ='Migration';
	$arHead1['$xen_vm_migrate_actions']['sortable'] = false;

	$table1->add_headrow("<input type='hidden' name='xen_id' value=$xen_appliance->id>");
	$table1->id = 'Tabelle';
	$table1->css = 'htmlobject_table';
	$table1->border = 1;
	$table1->cellspacing = 0;
	$table1->cellpadding = 3;
	$table1->form_action = $thisfile;
	$table1->autosort = true;
	$table1->identifier_type = "checkbox";
	$table1->bottom_buttons_name = "action_table1";
	$table1->identifier_name = "identifier_table1";
	$table1->head = $arHead1;
	$table1->body = $arBody1;
	if ($OPENQRM_USER->role == "administrator") {
		$table1->bottom = array('start', 'stop', 'reboot', 'remove', 'migrate', 'reload', 'console');
		$table1->identifier = 'xen_vm_name';
	}
	$table1->max = $xen_vm_count;

   // set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './tpl/' . 'xen-storage-vms.tpl.php');
	$t->setVar(array(
		'formaction' => $thisfile,
		'xen_server_table' => $table->get_string(),
		'xen_server_id' => $xen_appliance->id,
		'xen_server_name' => $xen_appliance->name,
		'xen_vm_table' => $table1->get_string(),
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
					$output[] = array('label' => 'Xen VM Manager', 'value' => xen_display($id));
				}
				break;
			case 'reload':
				foreach($_REQUEST['identifier'] as $id) {
					$output[] = array('label' => 'Xen VM Manager', 'value' => xen_display($id));
				}
				break;
		}

	} else {
		$output[] = array('label' => 'Xen VM Manager', 'value' => xen_select());
	}

} else if (strlen($xen_id)) {
	$output[] = array('label' => 'Xen VM Manager', 'value' => xen_display($xen_id));
} else  {
	$output[] = array('label' => 'Xen VM Manager', 'value' => xen_select());
}


?>
<script type="text/javascript">
	$("#progressbar").remove();
</script>
<?php

echo htmlobject_tabmenu($output);

?>


