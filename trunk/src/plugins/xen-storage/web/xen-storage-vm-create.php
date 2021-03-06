<!doctype html>
<html lang="en">
<head>
	<title>Xen create vm</title>
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
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/deployment.class.php";
require_once "$RootDir/include/htmlobject.inc.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/class/openqrm_server.class.php";

global $OPENQRM_SERVER_BASE_DIR;
global $RESOURCE_INFO_TABLE;
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
$refresh_delay=1;
$refresh_loop_max=20;

$xen_command = htmlobject_request('xen_command');
$xen_id = htmlobject_request('xen_id');
$xen_name = htmlobject_request('xen_name');
$xen_mac = htmlobject_request('xen_mac');
$xen_ip = htmlobject_request('xen_ip');
$xen_vm_bridge = htmlobject_request('xen_vm_bridge');
$xen_ram = htmlobject_request('xen_ram');
$xen_disk = htmlobject_request('xen_disk');
$xen_swap = htmlobject_request('xen_swap');
$xen_cpus = htmlobject_request('xen_cpus');
$xen_migrate_to_id = htmlobject_request('xen_migrate_to_id');
$xen_migrate_type = htmlobject_request('xen_migrate_type');
$xen_vm_boot_iso = htmlobject_request('xen_vm_boot_iso');
$xen_vm_boot_dev = htmlobject_request('xen_vm_boot_dev');


$xen_fields = array();
foreach ($_REQUEST as $key => $value) {
	if (strncmp($key, "xen_", 4) == 0) {
		$xen_fields[$key] = $value;
	}
}
unset($xen_fields["xen_command"]);


function redirect_mgmt($strMsg, $file, $xen_id) {
	global $thisfile;
	global $action;
	$url = $file.'?strMsg='.urlencode($strMsg).'&currenttab=tab0&xen_id='.$xen_id;
	echo "<meta http-equiv=\"refresh\" content=\"0; URL=$url\">";
	exit;
}

function wait_for_statfile($sfile) {
	global $refresh_delay;
	global $refresh_loop_max;
	$refresh_loop=0;
	while (!file_exists($sfile)) {
		sleep($refresh_delay);
		$refresh_loop++;
		flush();
		if ($refresh_loop > $refresh_loop_max)  {
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


function validate_input($var, $type) {
	switch ($type) {
		case 'string':
			// remove allowed chars
			$var = str_replace(".", "", $var);
			$var = str_replace("-", "", $var);
			$var = str_replace("_", "", $var);
			$var = str_replace("/", "", $var);
			for ($i = 0; $i<strlen($var); $i++) {
				if (!ctype_alpha($var[$i])) {
					if (!ctype_digit($var[$i])) {
						return false;
					}
				}
			}
			return true;
			break;
		case 'number';
			for ($i = 0; $i<strlen($var); $i++) {
				if (!ctype_digit($var[$i])) {
					return false;
				}
			}
			return true;
			break;
	}
}

$strMsg = '';
if(htmlobject_request('xen_command') != '') {
	if ($OPENQRM_USER->role == "administrator") {
		$event->log($xen_command, $_SERVER['REQUEST_TIME'], 5, "xen-create", "Processing create command", "", "", 0, 0, 0);

		switch ($xen_command) {
			case 'new':
				// send command to xen-host to create the new vm
				show_progressbar();
				if (!strlen($xen_name)) {
					$strMsg="Got empty vm name. Not creating new vm on Xen Host $xen_id";
					redirect_mgmt($strMsg, $thisfile, $xen_id);
				} else if (!validate_input($xen_name, 'string')) {
					$strMsg= "Invalid vm name. Not creating new vm on Xen Host $xen_id <br>(allowed characters are [a-z][A-z][0-9].-_)";
					redirect_mgmt($strMsg, $thisfile, $xen_id);
				}
				if (!strlen($xen_mac)) {
					$strMsg="Got empty mac-address. Not creating new vm on Xen Host $xen_id";
					redirect_mgmt($strMsg, $thisfile, $xen_id);
				}
				if (!strlen($xen_ram)) {
					$strMsg="Got empty Memory size. Not creating new vm on Xen Host $xen_id";
					redirect_mgmt($strMsg, $thisfile, $xen_id);
				} else if (!validate_input($xen_ram, 'number')) {
					$strMsg .= "Invalid vm memory $xen_ram. Not creating new vm on Xen Host $xen_id";
					redirect_mgmt($strMsg, $thisfile, $xen_id);
				}
				// empty disk + swap
				$xen_vm_disk_param = "";
				$xen_vm_swap_param = "";
				// check for cpu count is int
				if (!strlen($xen_cpus)) {
					$strMsg .= "Empty vm cpu number. Not creating new vm on Xen Host $xen_id";
					redirect_mgmt($strMsg, $thisfile, $xen_id);
				}
				if (!validate_input($xen_cpus, 'number')) {
					$strMsg .= "Invalid vm cpu number. Not creating new vm on Xen Host $xen_id";
					redirect_mgmt($strMsg, $thisfile, $xen_id);
				}
				// validate network params
				if (!strlen($xen_vm_bridge)) {
					$strMsg="Got empty Bridge config. Not creating new vm on Xen Host $xen_id";
					redirect_mgmt($strMsg, $thisfile, $xen_id);
				} else if (!validate_input($xen_vm_bridge, 'string')) {
					$strMsg .= "Invalid bridge config. Not creating new vm on Xen Host $xen_id";
					redirect_mgmt($strMsg, $thisfile, $xen_id);
				}
				// boot dev
				if (!strlen($xen_vm_boot_dev)) {
					$strMsg="Got empty boot-device config. Not creating new vm on Xen Host $xen_id";
					redirect_mgmt($strMsg, $thisfile, $xen_id);
				} else if (!validate_input($xen_vm_boot_dev, 'string')) {
					$strMsg .= "Invalid boot-device config. Not creating new vm on Xen Host $xen_id";
					redirect_mgmt($strMsg, $thisfile, $xen_id);
				}

				// boot iso / just if boot dev is iso
				if (!strcmp($xen_vm_boot_dev, "iso")) {
					if (!strlen($xen_vm_boot_iso)) {
						$strMsg .= "Got empty boot-iso config. Not creating new vm on Xen Host $xen_id";
						redirect_mgmt($strMsg, $thisfile, $xen_id);
					} else if (!validate_input($xen_vm_boot_iso, 'string')) {
						$strMsg .= "Invalid boot-iso config. Not creating new vm on Xen Host $xen_id";
						redirect_mgmt($strMsg, $thisfile, $xen_id);
					}
					$xen_vm_boot_iso = "-iso ".$xen_vm_boot_iso;
				} else {
					$xen_vm_boot_iso = "";
				}


				$xen_appliance = new appliance();
				$xen_appliance->get_instance_by_id($xen_id);
				$xen = new resource();
				$xen->get_instance_by_id($xen_appliance->resources);
				// unlink stat file
				$statfile="xen-stat/".$xen->id.".vm_list";
				if (file_exists($statfile)) {
					unlink($statfile);
				}
				// add resource + type + vhostid
				$resource = new resource();
				$resource_id=openqrm_db_get_free_id('resource_id', $RESOURCE_INFO_TABLE);
				$resource_ip="0.0.0.0";
				// send command to the openQRM-server
				$openqrm_server->send_command("openqrm_server_add_resource $resource_id $xen_mac $resource_ip");
				// set resource type
				$virtualization = new virtualization();
				$virtualization->get_instance_by_type("xen-storage-vm");
				// add to openQRM database
				$resource_fields["resource_id"]=$resource_id;
				$resource_fields["resource_ip"]=$resource_ip;
				$resource_fields["resource_mac"]=$xen_mac;
				$resource_fields["resource_localboot"]=0;
				$resource_fields["resource_vtype"]=$virtualization->id;
				$resource_fields["resource_vhostid"]=$xen->id;
				$resource->add($resource_fields);
				// wait for the new-resource hooks to run
				sleep(5);
				// send command
				$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/xen-storage/bin/openqrm-xen-storage-vm create -n $xen_name -m $xen_mac -r $xen_ram -c $xen_cpus -z $xen_vm_bridge -b $xen_vm_boot_dev $xen_vm_boot_iso -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
				$xen->send_command($xen->ip, $resource_command);
				// and wait for the resulting statfile
				if (!wait_for_statfile($statfile)) {
					$strMsg .= "Error during creating new Xen vm ! Please check the Event-Log<br>";
				} else {
					$strMsg .="Created new Xen vm resource $resource_id<br>";
				}
				redirect_mgmt($strMsg, "xen-storage-vm-manager.php", $xen_id);
				break;


			default:
				$event->log("$xen_command", $_SERVER['REQUEST_TIME'], 3, "xen-create", "No such event command ($xen_command)", "", "", 0, 0, 0);
				break;

		}
	}

} else {
	// refresh config parameter
	$xen_server_appliance = new appliance();
	$xen_server_appliance->get_instance_by_id($xen_id);
	$xen_server = new resource();
	$xen_server->get_instance_by_id($xen_server_appliance->resources);
	$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/xen-storage/bin/openqrm-xen-storage-vm post_bridge_config -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
	// remove current stat file
	$xen_server_resource_id = $xen_server->id;
	$statfile="xen-stat/".$xen_server_resource_id.".bridge_config";
	if (file_exists($statfile)) {
		unlink($statfile);
	}
	// send command
	$xen_server->send_command($xen_server->ip, $resource_command);
	// and wait for the resulting statfile
	if (!wait_for_statfile($statfile)) {
		echo "<b>Could not get bridge config status file! Please checks the event log";
		exit(0);
	}
}







function xen_create() {
	global $xen_id;
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_USER;
	global $thisfile;

	$xen_appliance = new appliance();
	$xen_appliance->get_instance_by_id($xen_id);
	$xen = new resource();
	$xen->get_instance_by_id($xen_appliance->resources);
	$resource_mac_gen = new resource();
	$resource_mac_gen->generate_mac();
	$suggested_mac = $resource_mac_gen->mac;
	$back_link = "<a href=\"xen-manager.php?action=reload&xen_id=$xen_id\">Back</a>";

	// bridge config
	$xen_vm_conf_file="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/xen-storage/web/xen-stat/".$xen->id.".bridge_config";
	$store = openqrm_parse_conf($xen_vm_conf_file);
	extract($store);

	// cpus array for the select
	$cpu_identifier_array = array();
	$cpu_identifier_array[] = array("value" => "1", "label" => "1 CPU");
	$cpu_identifier_array[] = array("value" => "2", "label" => "2 CPUs");
	$cpu_identifier_array[] = array("value" => "3", "label" => "3 CPUs");
	$cpu_identifier_array[] = array("value" => "4", "label" => "4 CPUs");

	// set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './tpl/' . 'xen-storage-vm-create.tpl.php');
	$t->setVar(array(
		'formaction' => $thisfile,
		'backlink' => $back_link,
		'xen_server_id' => $xen_id,
		'xen_server_name' => htmlobject_input('xen_name', array("value" => '', "label" => 'VM name'), 'text', 20),
		'xen_server_mac' => htmlobject_input('xen_mac', array("value" => $suggested_mac, "label" => 'Mac address'), 'text', 20),
		'xen_server_cpus' => htmlobject_select('xen_cpus', $cpu_identifier_array, 'CPUs'),
		'xen_server_ip' => htmlobject_input('xen_ip', array("value" => 'dhcp', "label" => 'Ip address'), 'text', 20),
		'xen_server_ram' => htmlobject_input('xen_ram', array("value" => '512', "label" => 'Memory (MB)'), 'text', 10),
		'xen_server_bridge_int' => $store['OPENQRM_PLUGIN_XEN_STORAGE_INTERNAL_BRIDGE'],
		'xen_server_bridge_ext' => $store['OPENQRM_PLUGIN_XEN_STORAGE_EXTERNAL_BRIDGE'],
		'hidden_xen_server_id' => "<input type=hidden name=xen_id value=$xen_id><input type=hidden name=xen_command value='new'>",
		'submit' => htmlobject_input('action', array("value" => 'new', "label" => 'Create'), 'submit'),
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;

}



$output = array();
// if admin
if ($OPENQRM_USER->role == "administrator") {
	$output[] = array('label' => 'Xen Storage Create VM', 'value' => xen_create());
}


?>
<script type="text/javascript">
	$("#progressbar").remove();
</script>
<?php

echo htmlobject_tabmenu($output);

?>


