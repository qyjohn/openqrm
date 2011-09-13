<!doctype html>
<html lang="en">
<head>
	<title>KVM create vm</title>
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
require_once "$RootDir/include/openqrm-server-config.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/deployment.class.php";
require_once "$RootDir/include/htmlobject.inc.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
global $RESOURCE_INFO_TABLE;

$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
$refresh_delay=1;
$refresh_loop_max=20;

// get the post parmater
$action = htmlobject_request('action');
$kvm_server_id = htmlobject_request('kvm_server_id');
$kvm_server_name = htmlobject_request('kvm_server_name');
$kvm_server_mac = htmlobject_request('kvm_server_mac');
$kvm_server_ram = htmlobject_request('kvm_server_ram');
$kvm_vm_bridge = htmlobject_request('kvm_vm_bridge');
$kvm_vm_boot_iso = htmlobject_request('kvm_vm_boot_iso');
$kvm_vm_boot_dev = htmlobject_request('kvm_vm_boot_dev');
$kvm_nic_model = htmlobject_request('kvm_nic_model');
$kvm_server_cpus = htmlobject_request('kvm_server_cpus');




function redirect_mgmt($strMsg, $file, $kvm_server_id) {
	global $thisfile;
	global $action;
	$url = $file.'?strMsg='.urlencode($strMsg).'&currenttab=tab0&kvm_server_id='.$kvm_server_id;
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
if(htmlobject_request('action') != '') {
	$event->log("$action", $_SERVER['REQUEST_TIME'], 5, "kvm-action", "Processing command $action", "", "", 0, 0, 0);
	if ($OPENQRM_USER->role == "administrator") {

		switch ($action) {
			case 'new':
				show_progressbar();
				// name check
				if (!strlen($kvm_server_name)) {
					$strMsg .= "Empty vm name. Not creating new vm on KVM Host $kvm_server_id";
					redirect_mgmt($strMsg, $thisfile, $kvm_server_id);
				} else if (!validate_input($kvm_server_name, 'string')) {
					$strMsg .= "Invalid vm name. Not creating new vm on KVM Host $kvm_server_id <br>(allowed characters are [a-z][A-z][0-9].-_)";
					redirect_mgmt($strMsg, $thisfile, $kvm_server_id);
				}
				if (!strlen($kvm_server_mac)) {
					$strMsg="Got empty mac-address. Not creating new vm on KVM Host $kvm_server_id";
					redirect_mgmt($strMsg, $thisfile, $kvm_server_id);
				}
				if (!strlen($kvm_server_ram)) {
					$strMsg="Got empty Memory size. Not creating new vm on KVM Host $kvm_server_id";
					redirect_mgmt($strMsg, $thisfile, $kvm_server_id);
				} else if (!validate_input($kvm_server_ram, 'number')) {
					$strMsg .= "Invalid vm memory $kvm_server_ram. Not creating new vm on KVM Host $kvm_server_id";
					redirect_mgmt($strMsg, $thisfile, $kvm_server_id);
				}
				// set empty disk + swap
				$kvm_server_disk_parameter = "";
				$kvm_server_swap_parameter = "";
				// check for cpu count is int
				if (!strlen($kvm_server_cpus)) {
					$strMsg .= "Empty vm cpu number. Not creating new vm on KVM Host $kvm_server_id";
					redirect_mgmt($strMsg, $thisfile, $kvm_server_id);
				}
				if (!validate_input($kvm_server_cpus, 'number')) {
					$strMsg .= "Invalid vm cpu number. Not creating new vm on KVM Host $kvm_server_id";
					redirect_mgmt($strMsg, $thisfile, $kvm_server_id);
				}
				// validate network params
				if (!strlen($kvm_vm_bridge)) {
					$strMsg="Got empty Bridge config. Not creating new vm on KVM Host $kvm_server_id";
					redirect_mgmt($strMsg, $thisfile, $kvm_server_id);
				} else if (!validate_input($kvm_vm_bridge, 'string')) {
					$strMsg .= "Invalid bridge config. Not creating new vm on KVM Host $kvm_server_id";
					redirect_mgmt($strMsg, $thisfile, $kvm_server_id);
				}
				// boot dev
				if (!strlen($kvm_vm_boot_dev)) {
					$strMsg="Got empty boot-device config. Not creating new vm on KVM Host $kvm_server_id";
					redirect_mgmt($strMsg, $thisfile, $kvm_server_id);
				} else if (!validate_input($kvm_vm_boot_dev, 'string')) {
					$strMsg .= "Invalid boot-device config. Not creating new vm on KVM Host $kvm_server_id";
					redirect_mgmt($strMsg, $thisfile, $kvm_server_id);
				}

				// boot iso / just if boot dev is iso
				if (!strcmp($kvm_vm_boot_dev, "iso")) {
					if (!strlen($kvm_vm_boot_iso)) {
						$strMsg .= "Got empty boot-iso config. Not creating new vm on KVM Host $kvm_server_id";
						redirect_mgmt($strMsg, $thisfile, $kvm_server_id);
					} else if (!validate_input($kvm_vm_boot_iso, 'string')) {
						$strMsg .= "Invalid boot-iso config. Not creating new vm on KVM Host $kvm_server_id";
						redirect_mgmt($strMsg, $thisfile, $kvm_server_id);
					}
					$kvm_vm_boot_iso = "-i ".$kvm_vm_boot_iso;
				} else {
					$kvm_vm_boot_iso = "";
				}

				// send command to kvm_server-host to create the new vm
				$kvm_appliance = new appliance();
				$kvm_appliance->get_instance_by_id($kvm_server_id);
				$kvm_server = new resource();
				$kvm_server->get_instance_by_id($kvm_appliance->resources);
				// final command
				$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/kvm-storage/bin/openqrm-kvm-storage-vm create -n $kvm_server_name -m $kvm_server_mac -r $kvm_server_ram -c $kvm_server_cpus -t $kvm_nic_model -z $kvm_vm_bridge -b $kvm_vm_boot_dev $kvm_vm_boot_iso -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
				// remove current stat file
				$kvm_server_resource_id = $kvm_server->id;
				$statfile="kvm-stat/".$kvm_server_resource_id.".vm_list";
				if (file_exists($statfile)) {
					unlink($statfile);
				}
				// add resource + type + vhostid
				$resource = new resource();
				$resource_id=openqrm_db_get_free_id('resource_id', $RESOURCE_INFO_TABLE);
				$resource_ip="0.0.0.0";
				// send command to the openQRM-server
				$openqrm_server->send_command("openqrm_server_add_resource $resource_id $kvm_server_mac $resource_ip");
				// set resource type
				$virtualization = new virtualization();
				$virtualization->get_instance_by_type("kvm-storage-vm");
				// add to openQRM database
				$resource_fields["resource_id"]=$resource_id;
				$resource_fields["resource_ip"]=$resource_ip;
				$resource_fields["resource_mac"]=$kvm_server_mac;
				$resource_fields["resource_localboot"]=0;
				$resource_fields["resource_vtype"]=$virtualization->id;
				$resource_fields["resource_vhostid"]=$kvm_server->id;
				$resource->add($resource_fields);

				// send command
				$kvm_server->send_command($kvm_server->ip, $resource_command);
				// and wait for the resulting statfile
				if (!wait_for_statfile($statfile)) {
					$strMsg .= "Error during creating new KVM vm ! Please check the Event-Log<br>";
				} else {
					$strMsg .="Created new KVM vm resource $resource_id<br>";
				}
				redirect_mgmt($strMsg, "kvm-storage-vm-manager.php", $kvm_server_id);
				break;

		}
	}


} else {
	// refresh config parameter
	$kvm_server_appliance = new appliance();
	$kvm_server_appliance->get_instance_by_id($kvm_server_id);
	$kvm_server = new resource();
	$kvm_server->get_instance_by_id($kvm_server_appliance->resources);
	$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/kvm-storage/bin/openqrm-kvm-storage-vm post_bridge_config -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
	// remove current stat file
	$kvm_server_resource_id = $kvm_server->id;
	$statfile="kvm-stat/".$kvm_server_resource_id.".bridge_config";
	if (file_exists($statfile)) {
		unlink($statfile);
	}
	// send command
	$kvm_server->send_command($kvm_server->ip, $resource_command);
	// and wait for the resulting statfile
	if (!wait_for_statfile($statfile)) {
		echo "<b>Could not get bridge config status file! Please checks the event log";
		exit(0);
	}
}




function kvm_server_create($kvm_server_id) {

	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_USER;
	global $thisfile;
	$kvm_server_appliance = new appliance();
	$kvm_server_appliance->get_instance_by_id($kvm_server_id);
	$kvm_server = new resource();
	$kvm_server->get_instance_by_id($kvm_server_appliance->resources);
	$resource_mac_gen = new resource();
	$resource_mac_gen->generate_mac();
	$suggested_mac = $resource_mac_gen->mac;

	// bridge config
	$kvm_vm_conf_file="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/kvm-storage/web/kvm-stat/".$kvm_server->id.".bridge_config";
	$store = openqrm_parse_conf($kvm_vm_conf_file);
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
	$t->setFile('tplfile', './tpl/' . 'kvm-storage-vm-create.tpl.php');
	$t->setVar(array(
		'formaction' => $thisfile,
		'kvm_server_id' => $kvm_server_id,
		'kvm_server_name' => htmlobject_input('kvm_server_name', array("value" => '', "label" => 'VM name'), 'text', 20),
		'kvm_server_cpus' => htmlobject_select('kvm_server_cpus', $cpu_identifier_array, 'CPUs'),
		'kvm_server_mac' => htmlobject_input('kvm_server_mac', array("value" => $suggested_mac, "label" => 'Mac address'), 'text', 20),
		'kvm_server_ram' => htmlobject_input('kvm_server_ram', array("value" => '512', "label" => 'Memory (MB)'), 'text', 10),
		'hidden_kvm_server_id' => "<input type=hidden name=kvm_server_id value=$kvm_server_id>",
		'kvm_server_bridge_net1' => $store['OPENQRM_PLUGIN_KVM_BRIDGE_NET1'],
		'kvm_server_bridge_net2' => $store['OPENQRM_PLUGIN_KVM_BRIDGE_NET2'],
		'kvm_server_bridge_net3' => $store['OPENQRM_PLUGIN_KVM_BRIDGE_NET3'],
		'kvm_server_bridge_net4' => $store['OPENQRM_PLUGIN_KVM_BRIDGE_NET4'],
		'kvm_server_bridge_net5' => $store['OPENQRM_PLUGIN_KVM_BRIDGE_NET5'],
		'submit' => htmlobject_input('action', array("value" => 'new', "label" => 'Create'), 'submit'),
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;

}



$output = array();
// if admin
if ($OPENQRM_USER->role == "administrator") {
	if (isset($kvm_server_id)) {
		$output[] = array('label' => 'KVM Create VM', 'value' => kvm_server_create($kvm_server_id));
	}
}


?>
<script type="text/javascript">
	$("#progressbar").remove();
</script>
<?php

echo htmlobject_tabmenu($output);

?>


