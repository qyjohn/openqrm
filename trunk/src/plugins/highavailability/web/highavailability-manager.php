<!doctype html>
<html lang="en">
<head>
	<title>HA Manager</title>
	<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
	<link rel="stylesheet" type="text/css" href="highavailability.css" />
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


$thisfile = basename($_SERVER['PHP_SELF']);
$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/include/htmlobject.inc.php";
global $OPENQRM_SERVER_BASE_DIR;



function redirect($strMsg, $currenttab = 'tab0', $url = '') {
	global $thisfile;
	if($url == '') {
		$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab='.$currenttab;
	}
	//	using meta refresh here because the resource and resourc class pre-sending header output
	echo "<meta http-equiv=\"refresh\" content=\"0; URL=$url\">";
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


if(htmlobject_request('action') != '' && $OPENQRM_USER->role == "administrator") {
	$strMsg = '';
	if(isset($_REQUEST['identifier'])) {
		switch (htmlobject_request('action')) {
			case 'set':
				show_progressbar();
				foreach($_REQUEST['identifier'] as $id) {
					if($id != 0) {
						$resource = new resource();
						$resource->get_instance_by_id($id);
						$custom_hat_arr = htmlobject_request("resource_custom_hat");
						$custom_hat = $custom_hat_arr[$id];
						$strMsg .= "Set custom timeout $custom_hat for resource $id <br>";
						$resource->set_resource_capabilities("HAT", $custom_hat);
					}
				}
				sleep(1);
				redirect($strMsg, "tab1");
				break;


			case 'enable':
				show_progressbar();
				foreach($_REQUEST['identifier'] as $id) {
					if($id != 0) {
						$appliance = new appliance();
						$appliance->get_instance_by_id($id);
						$appliance_fields = array();
						$appliance_fields["appliance_highavailable"]=1;
						$appliance->update($id, $appliance_fields);
						$strMsg .= "Enabled Highavailability for appliance $id <br>";
					}
				}
				sleep(1);
				redirect($strMsg, "tab0");
				break;

			case 'disable':
				foreach($_REQUEST['identifier'] as $id) {
					if($id != 0) {
						$appliance = new appliance();
						$appliance->get_instance_by_id($id);
						$appliance_fields = array();
						$appliance_fields["appliance_highavailable"]=0;
						$appliance->update($id, $appliance_fields);
						$strMsg .= "Disabled Highavailability for appliance $id <br>";
					}
				}
				sleep(1);
				redirect($strMsg, "tab0");
				break;

		}

	} //identifier
	#else { redirect('Please select a resource'); }
}




function ha_appliance_display() {
	global $OPENQRM_USER;
	global $thisfile;

	$appliance_tmp = new appliance();
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

	$arHead['appliance_kernelid'] = array();
	$arHead['appliance_kernelid']['title'] ='Kernel';

	$arHead['appliance_imageid'] = array();
	$arHead['appliance_imageid']['title'] ='Image';

	$arHead['appliance_resources'] = array();
	$arHead['appliance_resources']['title'] ='Resource <small>[id/ip]</small>';
	$arHead['appliance_resources']['sortable'] = false;

	$arHead['appliance_type'] = array();
	$arHead['appliance_type']['title'] ='Type';
	$arHead['appliance_type']['sortable'] = false;

	$arHead['appliance_ha'] = array();
	$arHead['appliance_ha']['title'] ='High-Available';
	$arHead['appliance_ha']['sortable'] = false;

	$arBody = array();
	$appliance_array = $appliance_tmp->display_overview($table->offset, $table->limit, $table->sort, $table->order);

	foreach ($appliance_array as $index => $appliance_db) {
		$appliance = new appliance();
		$appliance->get_instance_by_id($appliance_db["appliance_id"]);
		$resource = new resource();
		$appliance_resources=$appliance_db["appliance_resources"];
		// do not show appliances with the openQRM server itself as the resource
		if ($appliance_resources == 0) {
			continue;
		}

		if ($appliance_resources >=0) {
			// an appliance with a pre-selected resource
			$resource->get_instance_by_id($appliance_resources);
			$appliance_resources_str = "$resource->id/$resource->ip";
		} else {
			// an appliance with resource auto-select enabled
			$appliance_resources_str = "auto-select";
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

		$kernel = new kernel();
		$kernel->get_instance_by_id($appliance_db["appliance_kernelid"]);
		$image = new image();
		$image->get_instance_by_id($appliance_db["appliance_imageid"]);
		$virtualization = new virtualization();
		$virtualization->get_instance_by_id($appliance_db["appliance_virtualization"]);
		$appliance_virtualization_type=$virtualization->name;

		// ha or not ?
		if ($appliance_db["appliance_highavailable"] == 1) {
			$ha_icon = $active_state_icon;
		} else {
			$ha_icon = $inactive_state_icon;
		}
		$ha_img = "<img src=$ha_icon>";

		$arBody[] = array(
			'appliance_state' => "<img src=$state_icon>",
			'appliance_icon' => "<img width=24 height=24 src=$resource_icon_default>",
			'appliance_id' => $appliance_db["appliance_id"],
			'appliance_name' => $appliance_db["appliance_name"],
			'appliance_kernelid' => $kernel->name,
			'appliance_imageid' => $image->name,
			'appliance_resources' => "$appliance_resources_str",
			'appliance_type' => $appliance_virtualization_type,
			'appliance_ha' => $ha_img,
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
		$table->bottom = array('enable', 'disable');
		$table->identifier = 'appliance_id';
	}
	$table->max = $appliance_tmp->get_count();
	// set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './tpl/' . 'highavailability-select.tpl.php');
	$t->setVar(array(
		'ha_table' => $table->get_string(),
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;


	return $disp.$table->get_string();
}





// for setting a custom HA timeout
function ha_resource_config() {
	global $OPENQRM_USER;
	global $thisfile;

	$resource_tmp = new resource();
	$table = new htmlobject_db_table('resource_id');

	$arHead = array();
	$arHead['resource_state'] = array();
	$arHead['resource_state']['title'] ='';

	$arHead['resource_icon'] = array();
	$arHead['resource_icon']['title'] ='';

	$arHead['resource_id'] = array();
	$arHead['resource_id']['title'] ='ID';

	$arHead['resource_hostname'] = array();
	$arHead['resource_hostname']['title'] ='Name';

	$arHead['resource_mac'] = array();
	$arHead['resource_mac']['title'] ='Mac';

	$arHead['resource_ip'] = array();
	$arHead['resource_ip']['title'] ='Ip';

	$arHead['resource_type'] = array();
	$arHead['resource_type']['title'] ='Type';

	$arHead['resource_memtotal'] = array();
	$arHead['resource_memtotal']['title'] ='Memory';

	$arHead['resource_hat'] = array();
	$arHead['resource_hat']['title'] ='Timeout(sec)';

	$arBody = array();
	$resource_array = $resource_tmp->display_overview($table->offset, $table->limit, $table->sort, $table->order);
	array_shift($resource_array);

	foreach ($resource_array as $index => $resource_db) {
		// prepare the values for the array
		$resource = new resource();
		$resource->get_instance_by_id($resource_db["resource_id"]);
		$res_id = $resource->id;
		$mem_total = $resource_db['resource_memtotal'];
		$mem_used = $resource_db['resource_memused'];
		$mem = "$mem_used/$mem_total";
		$swap_total = $resource_db['resource_swaptotal'];
		$swap_used = $resource_db['resource_swapused'];
		$swap = "$swap_used/$swap_total";
		if ($resource->id == 0) {
			$resource_icon_default="/openqrm/base/img/logo.png";
			$resource_type = "openQRM";
			$resource_mac = "x:x:x:x:x:x";
			$custom_hat_input = "";
		} else {
			$resource_mac = $resource_db["resource_mac"];
			$resource_icon_default="/openqrm/base/img/resource.png";
			// the resource_type
			if ((strlen($resource->vtype)) && (!strstr($resource->vtype, "NULL"))){
				// find out what should be preselected
				$virtualization = new virtualization();
				$virtualization->get_instance_by_id($resource->vtype);
				if ($resource->id == $resource->vhostid) {
					// physical system
					$resource_type = "<nobr>".$virtualization->name."</nobr>";
				} else {
					// vm
					$resource_type = "<nobr>".$virtualization->name." on Res. ".$resource->vhostid."</nobr>";
				}
			} else {
				$resource_type = "Unknown";
			}

			// preset ha timeout
			$custom_hat = $resource->get_resource_capabilities("HAT");
			if (!strlen($custom_hat)) {
				$custom_hat = 240;
			}
			$custom_hat_input = "<input type=text size=5 name=resource_custom_hat[$res_id] value=$custom_hat>";

		}
		$state_icon="/openqrm/base/img/$resource->state.png";
		// idle ?
		if (("$resource->imageid" == "1") && ("$resource->state" == "active")) {
			$state_icon="/openqrm/base/img/idle.png";
		}
		if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/".$state_icon)) {
			$state_icon="/openqrm/base/img/unknown.png";
		}


		$arBody[] = array(
			'resource_state' => "<img src=$state_icon>",
			'resource_icon' => "<img width=24 height=24 src=$resource_icon_default>",
			'resource_id' => $resource_db["resource_id"],
			'resource_hostname' => $resource_db["resource_hostname"],
			'resource_mac' => $resource_mac,
			'resource_ip' => $resource_db["resource_ip"],
			'resource_type' => $resource_type,
			'resource_memtotal' => $mem,
			'resource_hat' => $custom_hat_input,
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
		$table->bottom = array('set');
		$table->identifier = 'resource_id';
		$table->identifier_disabled = array(0);
	}
	$table->max = $resource_tmp->get_count('all') + 1; // adding openqrmserver
	$table->add_headrow("<input type=\"hidden\" name=\"currenttab\" value=\"tab1\">");

  // set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './tpl/highavailability-configuration.tpl.php');
	$t->setVar(array(
		'resource_table' => $table->get_string(),
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;


}





$output = array();
$output[] = array('label' => 'High-Availability Manager', 'value' => ha_appliance_display());
$output[] = array('label' => 'HA-Configuration', 'value' => ha_resource_config());


?>
<script type="text/javascript">
	$("#progressbar").remove();
</script>
<?php

echo htmlobject_tabmenu($output);
?>


