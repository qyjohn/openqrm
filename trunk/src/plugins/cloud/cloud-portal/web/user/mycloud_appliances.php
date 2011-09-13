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
$DocRoot = $_SERVER["DOCUMENT_ROOT"];
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/deployment.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/include/htmlobject.inc.php";
// special cloud classes
require_once "$RootDir/plugins/cloud/class/clouduser.class.php";
require_once "$RootDir/plugins/cloud/class/cloudrequest.class.php";
require_once "$RootDir/plugins/cloud/class/cloudconfig.class.php";
require_once "$RootDir/plugins/cloud/class/cloudmailer.class.php";
require_once "$RootDir/plugins/cloud/class/cloudimage.class.php";
require_once "$RootDir/plugins/cloud/class/cloudappliance.class.php";
require_once "$RootDir/plugins/cloud/class/cloudnat.class.php";
require_once "$RootDir/plugins/cloud/class/cloudicon.class.php";

global $OPENQRM_SERVER_BASE_DIR;
$refresh_delay=5;

$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $CLOUD_REQUEST_TABLE;
$cloud_object_icon_size=48;
global $cloud_object_icon_size;

// who are you ?
$auth_user = $_SERVER['PHP_AUTH_USER'];
global $auth_user;




function my_cloud_appliances() {

	global $thisfile;
	global $auth_user;
	global $RootDir;
	global $DocRoot;
	global $cloud_object_icon_size;

	$sshterm_enabled = false;
	$collectd_graph_enabled = false;
	$disk_resize_enabled = false;
	$private_image_enabled = false;
	$show_unpause_button = false;
	$show_pause_button = false;
	$show_application_ha = false;

	// check if to show sshterm-login
	$cc_conf = new cloudconfig();
	$show_sshterm_login = $cc_conf->get_value(17);	// show_sshterm_login
	if (!strcmp($show_sshterm_login, "true")) {
		// is sshterm plugin enabled + started ?
		if (file_exists("$RootDir/plugins/sshterm/.running")) {
			$sshterm_enabled = true;
		}
	}
	// collectd enabled ?
	$show_collectd_graph = $cc_conf->get_value(19);	// show_collectd_graph
	if (!strcmp($show_collectd_graph, "true")) {
		if (file_exists("$RootDir/plugins/collectd/.running")) {
			$collectd_graph_enabled = true;
		}
	}
	// disk-resize enabled ?
	$show_disk_resize = $cc_conf->get_value(20);	// show_disk_resize
	if (!strcmp($show_disk_resize, "true")) {
		$disk_resize_enabled = true;
	}
	// private-image enabled ?
	$show_private_image = $cc_conf->get_value(21);	// show_private_image
	if (!strcmp($show_private_image, "true")) {
		$private_image_enabled = true;
	}

	// application ha
	$show_ha_checkbox = $cc_conf->get_value(10);	// show_ha_checkbox
	if (!strcmp($show_ha_checkbox, "true")) {
		// is drdbmc enabled ?
		if (file_exists("$RootDir/plugins/drbdmc/.running")) {
			$show_application_ha = true;
		}
	}


	$appliance_tmp = new appliance();
	$table = new htmlobject_db_table('appliance_id', 'DESC');

	$arHead = array();
	$arHead['appliance_state'] = array();
	$arHead['appliance_state']['title'] ='';

	$arHead['appliance_icon'] = array();
	$arHead['appliance_icon']['title'] ='';

	$arHead['appliance_id'] = array();
	$arHead['appliance_id']['title'] ='编号';

	$arHead['appliance_name'] = array();
	$arHead['appliance_name']['title'] ='名称';

	$arHead['appliance_config'] = array();
	$arHead['appliance_config']['title'] ='配置';

	$arHead['appliance_disk_size'] = array();
	$arHead['appliance_disk_size']['title'] ='磁盘（MB）';

	$arHead['appliance_comment'] = array();
	$arHead['appliance_comment']['title'] ='说明';

	$arHead['appliance_cloud_state'] = array();
	$arHead['appliance_cloud_state']['title'] ='状态';

	$arHead['appliance_cloud_action'] = array();
	$arHead['appliance_cloud_action']['title'] ='操作';

	$arBody = array();

	// we need to find only the appliances from the user
	$clouduser = new clouduser();
	$clouduser->get_instance_by_name($auth_user);

	$cloudreq_array = array();
	$cloudreq = new cloudrequest();
	$cloudreq_array = $cloudreq->get_all_ids_per_user($clouduser->id);
	$my_appliances = array();
	// build an array of our appliance id's
	foreach ($cloudreq_array as $cr) {
		$cl_tmp_req = new cloudrequest();
		$cr_id = $cr['cr_id'];
		$cl_tmp_req->get_instance_by_id($cr_id);
		if ((strlen($cl_tmp_req->appliance_id)) && ($cl_tmp_req->appliance_id != 0)) {
			$one_app_id_arr = explode(",", $cl_tmp_req->appliance_id);
			foreach ($one_app_id_arr as $aid) {
				$my_appliances[] .= $aid;
			}
		}
	}

	// now we go over all our appliances from the users request list
	foreach ($my_appliances as $appid) {
		$appliance = new appliance();
		$appliance->get_instance_by_id($appid);

		$sshterm_login = false;
		$appliance_resources_str="";
		$res_ip_loop = 0;
		$resource = new resource();
		$appliance_resources=$appliance->resources;
		if ($appliance_resources >=0) {
			// an appliance with a pre-selected resource
			// get its ips from the ip-mgmt

			// ######################## ip-mgmt find users ips ###############################
			// here we check which ip to send to the user
			// check ip-mgmt
			$sshterm_login_ip = '';
			$show_ip_mgmt = $cc_conf->get_value(26);	// ip-mgmt enabled ?
			if (!strcmp($show_ip_mgmt, "true")) {
				if (file_exists("$RootDir/plugins/ip-mgmt/.running")) {
					require_once "$RootDir/plugins/ip-mgmt/class/ip-mgmt.class.php";

					$ip_mgmt = new ip_mgmt();
					$appliance_first_nic_ip_mgmt_id = $ip_mgmt->get_id_by_appliance($appliance->id, 1);
					if ($appliance_first_nic_ip_mgmt_id > 0) {
						$appliance_ip_mgmt_config_arr = $ip_mgmt->get_instance('id', $appliance_first_nic_ip_mgmt_id);
						if (isset($appliance_ip_mgmt_config_arr['ip_mgmt_address'])) {
							$sshterm_login_ip = $appliance_ip_mgmt_config_arr['ip_mgmt_address'];
							$appliance_resources_str = $appliance_ip_mgmt_config_arr['ip_mgmt_address'];
							$sshterm_login = true;
						}
					}
				}
			}

			if (!strlen($sshterm_login_ip)) {
				// in case no external ip was given to the appliance we show the internal ip
				$resource->get_instance_by_id($appliance->resources);
				$appliance_resources_str = $resource->ip;
				$sshterm_login_ip =  $resource->ip;
				$sshterm_login = true;
			}

			// check if we need to NAT the ip address
			$cn_conf = new cloudconfig();
			$cn_nat_enabled = $cn_conf->get_value(18);  // 18 is cloud_nat
			if (!strcmp($cn_nat_enabled, "true")) {
				$cn = new cloudnat();
				$appliance_resources_str = $cn->translate($appliance_resources_str);
				$sshterm_login_ip = $cn->translate($sshterm_login_ip);
			}



		} else {
			// an appliance with resource auto-select enabled
			$appliance_resources_str = "auto-select";
			$sshterm_login = false;
		}

		// active or inactive
		$resource_icon_default="/cloud-portal/img/resource.png";
		$active_state_icon="/cloud-portal/img/active.png";
		$inactive_state_icon="/cloud-portal/img/idle.png";
		$starting_state_icon="/cloud-portal/img/starting.png";
		if ($appliance->stoptime == 0 || $appliance_resources == 0)  {
			$state_icon=$active_state_icon;
		} else {
			$state_icon=$inactive_state_icon;
			$sshterm_login = false;
		}
		// state
		$cloud_appliance = new cloudappliance();
		$cloud_appliance->get_instance_by_appliance_id($appliance->id);
		switch ($cloud_appliance->state) {
			case 0:
				$cloudappliance_state = "paused";
				$sshterm_login = false;
				$show_pause_button = false;
				$show_unpause_button = true;
				break;
			case 1:
				$cloudappliance_state = "active";
				$show_pause_button = true;
				break;
		}
		// use resource-state in case of a starting appliance
		$resource->get_instance_by_id($appliance->resources);
		if (strcmp($resource->state, "active")) {
			$state_icon=$starting_state_icon;
			$sshterm_login = false;
			$show_pause_button = false;
			$show_unpause_button = false;
			$show_application_ha = false;
		}
		// check if we have a custom icon for the cloudappliance
		$cloud_icon = new cloudicon();
		$cloud_icon->get_instance_by_details($clouduser->id, 2, $cloud_appliance->id);
		if (strlen($cloud_icon->filename)) {
			$resource_icon_default="custom-icons/".$cloud_icon->filename;
		}

		$kernel = new kernel();
		$kernel->get_instance_by_id($appliance->kernelid);
		$image = new image();
		$image->get_instance_by_id($appliance->imageid);
		$virtualization = new virtualization();
		$virtualization->get_instance_by_id($appliance->virtualization);
		$appliance_virtualization_type=$virtualization->name;
		// image disk size
		$cloud_image = new cloudimage();
		$cloud_image->get_instance_by_image_id($image->id);
		$cloud_image_disk_size = $cloud_image->disk_size;


		// prepare actions
		$cloudappliance_action = "";
		// sshterm login
		if ($sshterm_enabled) {
			if ($sshterm_login) {
				$cloudappliance_action .= "<input type=hidden name=\"sshterm_login_ip[$cloud_appliance->id]\" value=\"$sshterm_login_ip\">";
				$cloudappliance_action .= "<input type=\"image\" name=\"action\" value=\"login\" src=\"../img/login.png\" alt=\"login\">&nbsp;";
			}
		}
		// application ha
		if ($show_application_ha) {
			$drbdmc_gui="drbdmc/drbdmc-gui.php";
			$icon_size = "width='21' height='21'";
			$icon_title = "Configure appliaction highavailability";
			$drbdmc_url = "<a style=\"text-decoration:none\" href=\"#\" onClick=\"javascript:window.open('$drbdmc_gui','','location=0,status=0,scrollbars=1,width=1024,height=768,left=50,top=20,screenX=50,screenY=20');\">
				<image border=\"0\" $icon_size alt=\"".$icon_title."\" title=\"".$icon_title."\" src=\"../img/ha_console.png\">
				</a>";

			$cloudappliance_action .= $drbdmc_url;
		}
		// regular actions
		if ($show_pause_button) {
			$cloudappliance_action .= "<input type=\"image\" name=\"action\" value=\"pause\" src=\"../img/pause.png\" alt=\"Pause\">&nbsp;";
			$cloudappliance_action .= "<input type=\"image\" name=\"action\" value=\"restart\" src=\"../img/restart.png\" alt=\"Restart\">&nbsp;";
		}
		if ($show_unpause_button) {
			$cloudappliance_action .= "<input type=\"image\" name=\"action\" value=\"unpause\" src=\"../img/unpause.png\" alt=\"Un-Pause\">&nbsp;";
		}
		if ($collectd_graph_enabled) {
			$collectd_graph_link="/cloud-portal/user/users/".$clouduser->name."/".$appliance->name."/index.html";
			if (file_exists($DocRoot.$collectd_graph_link)) {
				$cloudappliance_action .= "<a href=\"$collectd_graph_link\" target=\"_BLANK\">";
				$cloudappliance_action .= "<img src=\"../img/graphs.png\" border=\"0\" width=\"25\" height=\"25\" alt=\"System Graphs\" title=\"System Graphs\">&nbsp;";
				$cloudappliance_action .= "</a>";
			} else {
				$cloudappliance_action .= "<img src=\"../img/progress.gif\" border=\"0\" width=\"25\" height=\"25\" alt=\"Collecting Data, Graphs will be available soon\" title=\"Collecting Data, Graphs will be available soon\">&nbsp;";
			}
		}
		// disk-resize ?
		if ($disk_resize_enabled) {
			$cloud_appliance_disk_size = "<input type=text name=\"appliance_disk_resize[$cloud_appliance->id]\" value=\"$cloud_image_disk_size\" size=4><input type=hidden name=\"currenttab\" value=\"tab3\">";
		} else {
			$cloud_appliance_disk_size = "$cloud_image_disk_size";
		}
		// format image column
		$config_column = "<b>Kernel:</b> ".$kernel->name."<br><b>Image:</b> ".$image->name."<br><b>Type:</b> ".$appliance_virtualization_type."<br><b>IP:</b>".$appliance_resources_str;

		$appliance_comment = $appliance->comment;
		$arBody[] = array(
			'appliance_state' => "<img width=\"16\" height=\"16\" src=\"".$state_icon."\">",
			'appliance_icon' => "<img width=\"".$cloud_object_icon_size."\" height=\"".$cloud_object_icon_size."\" src=\"".$resource_icon_default."\"><input type=hidden name=\"currenttab\" value=\"tab3\">
				<br><br><a href=\"#\" onClick=\"javascript:window.open('mycloudiconupload.php?object_type=2&object_id=$cloud_appliance->id','','location=0,status=0,scrollbars=1,width=390,height=170,left=200,top=150,screenX=200,screenY=150');\"><small>Upload Icon</small></a>",
			'appliance_id' => $cloud_appliance->id,
			'appliance_name' => $appliance->name."<br><br><a href=\"/cloud-portal/user/mycloud.php?currenttab=tab1\"><small>(Request: ". $cloud_appliance->cr_id.")<small></a><br><br>",
			'appliance_config' => $config_column,
			'appliance_disk_size' => $cloud_appliance_disk_size,
			'appliance_comment' => "<input type=text name=\"appliance_comment[$cloud_appliance->id]\" value=\"$appliance_comment\">",
			'appliance_cloud_state' => $cloudappliance_state,
			'appliance_cloud_action' => $cloudappliance_action,
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
	$table->sort = "";
	$command_array = array('pause', 'unpause', 'restart', 'set-comment');
	if ($sshterm_enabled) {
		$command_array[] = 'login';
	}
	if ($disk_resize_enabled) {
		$command_array[] = 'resize';
	}
	if ($private_image_enabled) {
		$command_array[] = 'private';
	}

	$table->bottom = $command_array;
	$table->identifier = 'appliance_id';
	$table->max = count($my_appliances);

	//------------------------------------------------------------ set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './' . 'mycloudappliances-tpl.php');
	$t->setVar(array(
		'thisfile' => $thisfile,
		'currentab' => htmlobject_input('currenttab', array("value" => 'tab3', "label" => ''), 'hidden'),
		'cloud_appliance_table' => $table->get_string(),
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;
}


?>

