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

$thisfile = basename($_SERVER['PHP_SELF']);
$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/include/htmlobject.inc.php";

function redirect($strMsg, $currenttab = 'tab0', $url = '') {
	global $thisfile;
	if($url == '') {
		$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab='.$currenttab;
	}
	//	using meta refresh here because the appliance and resourc class pre-sending header output
	echo "<meta http-equiv=\"refresh\" content=\"0; URL=$url\">";
}


if(htmlobject_request('action') != '') {
	$strMsg = '';
	$return_msg = '';
	$openqrm_server = new openqrm_server();
	$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
	global $OPENQRM_SERVER_IP_ADDRESS;
	if(strtolower(OPENQRM_USER_ROLE_NAME) == 'administrator') {
		switch (htmlobject_request('action')) {
			case 'start':
				if(isset($_REQUEST['identifier'])) {
					foreach($_REQUEST['identifier'] as $id) {
						$appliance = new appliance();
						$appliance->get_instance_by_id($id);
						$resource = new resource();
						if ($appliance->resources <0) {
							// an appliance with resource auto-select enabled
							$appliance_virtualization=$appliance->virtualization;
							$appliance->find_resource($appliance_virtualization);
							$appliance->get_instance_by_id($id);
							if ($appliance->resources <0) {
								$strMsg .= "Could not find any available resource for appliance $id!<br>";
								continue;
							}
						}
						$resource->get_instance_by_id($appliance->resources);
						if ($appliance->resources == 0) {
							$strMsg .= "An appliance with the openQRM-server as resource is always active!<br>";
							continue;
						}
						if (!strcmp($appliance->state, "active"))  {
							$strMsg .= "Not starting already started appliance $id <br>";
							continue;
						}
						// check that resource is idle
						$app_resource = new resource();
						$app_resource->get_instance_by_id($appliance->resources);
						// resource has ip ?
						if (!strcmp($app_resource->ip, "0.0.0.0")) {
							$strMsg .= "Resource $app_resource->id is not in idle state. Not starting appliance $id <br>";
							continue;
						}
						// resource assinged to imageid 1 ?
						if ($app_resource->imageid != 1) {
							$strMsg .= "Resource $app_resource->id is not in idle state. Not starting appliance $id <br>";
							continue;
						}
						// resource active
						if (strcmp($app_resource->state, "active")) {
							$app_resource_virtualization = new virtualization();
							$app_resource_virtualization->get_instance_by_id($app_resource->vtype);
							// allow waking up physical systems via out-of-band-management plugins
							if (!strstr($app_resource_virtualization->name, "Host")) {
								if ($app_resource_virtualization->id != 1) {
									$strMsg .= "Resource $app_resource->id is not in idle state. Not starting appliance $id <br>";
									continue;
								}
							}
						}
						// if no errors then we start the appliance
						$kernel = new kernel();
						$kernel->get_instance_by_id($appliance->kernelid);
						// send command to the openQRM-server
						$openqrm_server->send_command("openqrm_assign_kernel $resource->id $resource->mac $kernel->name");
						// start appliance
						$return_msg .= $appliance->start();
						$strMsg .= "Started appliance $id <br>";
					}
				}
				redirect($strMsg);
				break;

			case 'stop':
				if(isset($_REQUEST['identifier'])) {
					foreach($_REQUEST['identifier'] as $id) {
						$appliance = new appliance();
						$appliance->get_instance_by_id($id);
						$resource = new resource();
						$resource->get_instance_by_id($appliance->resources);
						if ($appliance->resources == 0) {
							$strMsg .= "An appliance with the openQRM-server as resource is always active!<br>";
							continue;
						}
						if (!strcmp($appliance->state, "stopped"))  {
							$strMsg .= "Not stopping already stopped appliance $id <br>";
							continue;
						}
						// here we stop
						$kernel = new kernel();
						$kernel->get_instance_by_id($appliance->kernelid);
						// send command to the openQRM-server
						$openqrm_server->send_command("openqrm_assign_kernel $resource->id $resource->mac default");
						// stop appliance
						$return_msg .= $appliance->stop();
						$strMsg .= "Stopped appliance $id <br>";
					 }
				}
				redirect($strMsg);
				break;

			case 'remove':
				$appliance = new appliance();
				if(isset($_REQUEST['identifier'])) {
					foreach($_REQUEST['identifier'] as $id) {
						$appliance->get_instance_by_id($id);
						// we can remove active openQRM-server appliances
						if ($appliance->resources == 0) {
							$return_msg .= $appliance->remove($id);
							$strMsg .= "Removed appliance $id <br>";
							continue;
						}
						if (!strcmp($appliance->state, "active"))  {
							$strMsg .= "Not removing active appliance $id <br>";
							continue;
						}
						$appliance->remove($id);
						$strMsg .= "Removed appliance $id <br>";
					 }
				}
				redirect($strMsg);
				break;
		}
	}
}



function appliance_htmlobject_select($name, $value, $title = '', $selected = '') {
	$html = new htmlobject_select();
	$html->name = $name;
	$html->title = $title;
	$html->selected = $selected;
	$html->text_index = array("value" => "value", "text" => "label");
	$html->text = $value;
	return $html->get_string();
}


function appliance_display() {
	global $OPENQRM_USER;
	global $thisfile;
	global $RootDir;

	$appliance_tmp = new appliance();
	$table = new htmlobject_db_table('appliance_id');

	$disp = '<h1>器件列表</h1>';
	$disp .= '<br>';

	$arHead = array();
	$arHead['appliance_state'] = array();
	$arHead['appliance_state']['title'] ='';
	$arHead['appliance_state']['sortable'] = false;

	$arHead['appliance_icon'] = array();
	$arHead['appliance_icon']['title'] ='';
	$arHead['appliance_icon']['sortable'] = false;

	$arHead['appliance_id'] = array();
	$arHead['appliance_id']['title'] ='编号';

	$arHead['appliance_name'] = array();
	$arHead['appliance_name']['title'] ='名称';

	$arHead['appliance_kernelid'] = array();
	$arHead['appliance_kernelid']['title'] ='内核';
	$arHead['appliance_kernelid']['hidden'] = true;

	$arHead['appliance_imageid'] = array();
	$arHead['appliance_imageid']['title'] ='映像';
	$arHead['appliance_imageid']['hidden'] = true;

	$arHead['appliance_resources'] = array();
	$arHead['appliance_resources']['title'] ='计算资源';
	$arHead['appliance_resources']['hidden'] = true;

	$arHead['appliance_type'] = array();
	$arHead['appliance_type']['title'] ='类别';
	$arHead['appliance_type']['hidden'] = true;
	$arHead['appliance_type']['sortable'] = false;

	$arHead['appliance_values'] = array();
	$arHead['appliance_values']['title'] ='';
	$arHead['appliance_values']['sortable'] = false;

	$arHead['appliance_comment'] = array();
	$arHead['appliance_comment']['title'] ='';
	$arHead['appliance_comment']['sortable'] = false;

	$arHead['appliance_edit'] = array();
	$arHead['appliance_edit']['title'] ='';
	$arHead['appliance_edit']['sortable'] = false;
	if(strtolower(OPENQRM_USER_ROLE_NAME) != 'administrator') {
		$arHead['appliance_edit']['hidden'] = true;
	}

	$arBody = array();
	$appliance_array = $appliance_tmp->display_overview($table->offset, $table->limit, $table->sort, $table->order);

	$resource_icon_default="/openqrm/base/img/resource.png";
	$active_state_icon="/openqrm/base/img/active.png";
	$inactive_state_icon="/openqrm/base/img/idle.png";

	foreach ($appliance_array as $index => $appliance_db) {
		$appliance = new appliance();
		$appliance->get_instance_by_id($appliance_db["appliance_id"]);
		$resource = new resource();
		$appliance_resources=$appliance_db["appliance_resources"];
		$kernel = new kernel();
		$kernel->get_instance_by_id($appliance_db["appliance_kernelid"]);
		$image = new image();
		$image->get_instance_by_id($appliance_db["appliance_imageid"]);
		$virtualization = new virtualization();
		$virtualization->get_instance_by_id($appliance_db["appliance_virtualization"]);
		$appliance_virtualization_name=$virtualization->name;
		$virtualization_plugin_name = str_replace("-vm", "", $virtualization->type);
		$resource_is_local_server = false;
		// special vm-manager for ..-storage plugins
		if (strpos($virtualization_plugin_name, "-storage")) {
			$vm_manager_file = $virtualization_plugin_name."-vm-manager.php";
		} else {
			$vm_manager_file = $virtualization_plugin_name."-manager.php";
		}

		if ($appliance_resources >=0) {
			// an appliance with a pre-selected resource
			$resource->get_instance_by_id($appliance_resources);
			$resource_state_icon="/openqrm/base/img/$resource->state.png";
			if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/".$resource_state_icon)) {
				$resource_state_icon="/openqrm/base/img/unknown.png";
			}
			// idle ?
			if (("$resource->imageid" == "1") && ("$resource->state" == "active")) {
				$resource_state_icon="/openqrm/base/img/idle.png";
			}

			// if virtual, link to vm manager
			if (strpos($virtualization->type, "-vm")) {
				$host_resource = new resource();
				$host_resource->get_instance_by_id($resource->vhostid);
				$host_virtualization = new virtualization();
				$host_virtualization_name = str_replace("-vm", "", $virtualization->type);
				$host_virtualization->get_instance_by_type($host_virtualization_name);
				$host_appliance = new appliance();
				$host_appliance->get_instance_by_virtualization_and_resource($host_virtualization->id, $resource->vhostid);
				$link_to_vm_manager_resource_detail = "/openqrm/base/plugins/".$host_virtualization_name."/".$vm_manager_file."?&currenttab=tab0&action=select&identifier[]=".$host_appliance->id;
				$appliance_resources_str = " <a href='".$link_to_vm_manager_resource_detail."'><img width=12 height=12 src=".$resource_state_icon."> ".$resource->id." / ".$resource->ip."</a>";
				$link_to_vm_manager = "/openqrm/base/plugins/".$host_virtualization_name."/".$vm_manager_file."?&currenttab=tab0";
				$appliance_virtualization_name = " <a href='".$link_to_vm_manager."'>".$virtualization->name."</a>";
			} else {
				$appliance_resources_str = " <a href='/openqrm/base/server/resource/resource-overview.php'><img width=12 height=12 src=".$resource_state_icon."> ".$resource->id." / ".$resource->ip."</a>";
			}
			//
			if (strstr($resource->capabilities, "TYPE=local-server")) {
				$resource_is_local_server = true;
			}


		} else {
			// an appliance with resource auto-select enabled
			$appliance_resources_str = "auto-select";
			$link_to_vm_manager = "/openqrm/base/plugins/".$virtualization_plugin_name."/".$vm_manager_file."?&currenttab=tab0";
			$appliance_virtualization_name = " <a href='".$link_to_vm_manager."'>".$virtualization->name."</a>";
		}
		// if its a virtualization host link to vm-manager
		if (strpos($virtualization->name, " Host")) {
			$link_to_vm_manager_resource_detail = "/openqrm/base/plugins/".$virtualization->type."/".$vm_manager_file."?&currenttab=tab0&action=select&identifier[]=".$appliance->id;
			$appliance_virtualization_name = " <a href='".$link_to_vm_manager_resource_detail."'>".$virtualization->name."</a>";
		}
		// active or inactive
		$resource_icon_default="/openqrm/base/img/resource.png";
		$active_state_icon="/openqrm/base/img/active.png";
		$inactive_state_icon="/openqrm/base/img/idle.png";
		if ($appliance->stoptime == 0 || $appliance_resources == 0)  {
			$state_icon=$active_state_icon;
		} else {
			$state_icon=$inactive_state_icon;
		}
		// additional local-server server appliances will still appear idle/stopped
		// only the master appliance can be started/stopped
		//		if ($resource_is_local_server) {
		//			$state_icon=$active_state_icon;
		//		}

		// appliance edit
		$strEdit = '<a href="appliance-edit.php?appliance_id='.$appliance_db["appliance_id"].'&currenttab=tab2"><img src="../../img/edit.png" width="24" height="24" alt="edit"/> Edit</a>';
		// link to image edit
		if ($image->id > 0) {
			$image_edit_link = '<a href="/openqrm/base/server/image/image-edit.php?image_id='.$image->id.'&currenttab=tab2">'.$image->name.'</a>';
		} else {
			$image_edit_link = $image->name;
		}

		$str = '<b>Kernel:</b> '.$kernel->name.'<br>
				<b>Image:</b> '.$image_edit_link.'<br>
				<b>Resource:</b> '.$appliance_resources_str.'<br>
				<b>Type:</b> '.$appliance_virtualization_name;

		// build the plugin link section
		$appliance_link_section = '';
		$plugin = new plugin();
		$enabled_plugins = $plugin->enabled();
		foreach ($enabled_plugins as $index => $plugin_name) {
			$plugin_appliance_link_section_hook = "$RootDir/plugins/$plugin_name/openqrm-$plugin_name-appliance-link-hook.php";
			if (file_exists($plugin_appliance_link_section_hook)) {
				require_once "$plugin_appliance_link_section_hook";
				$appliance_get_link_function="get_"."$plugin_name"."_appliance_link";
				$appliance_get_link_function=str_replace("-", "_", $appliance_get_link_function);
				$appliance_link_section .= $appliance_get_link_function($appliance->id);
			}
		}
		$appliance_comment = $appliance_db["appliance_comment"];
		$appliance_comment .= "<br><hr>";
		$appliance_comment .= $appliance_link_section;

		$arBody[] = array(
			'appliance_state' => "<img src=$state_icon>",
			'appliance_icon' => "<img width=24 height=24 src=$resource_icon_default>",
			'appliance_id' => $appliance_db["appliance_id"],
			'appliance_name' => $appliance_db["appliance_name"],
			'appliance_kernelid' => '',
			'appliance_imageid' => '',
			'appliance_resources' => '',
			'appliance_values' => $str,
			'appliance_type' => '',
			'appliance_comment' => $appliance_comment,
			'appliance_edit' => $strEdit,
		);

	}

	$table->id = 'Tabelle';
	$table->css = 'htmlobject_table';
	$table->border = 1;
	$table->cellspacing = 0;
	$table->cellpadding = 3;
	$table->form_action = $thisfile;
	$table->head = $arHead;
	$table->body = $arBody;
	if ($OPENQRM_USER->role == "administrator") {
		$table->bottom = array('start', 'stop', 'remove');
		$table->identifier = 'appliance_id';
	}
	$table->max = $appliance_tmp->get_count();
	#$table->limit = 10;

	return $disp.$table->get_string();
}




$output = array();
$output[] = array('label' => '器件列表', 'value' => appliance_display());
if(strtolower(OPENQRM_USER_ROLE_NAME) == 'administrator') {
	$output[] = array('label' => '创建器件', 'target' => 'appliance-new.php');
}


?>
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
<link rel="stylesheet" type="text/css" href="appliance.css" />
<?php
echo htmlobject_tabmenu($output);
?>


