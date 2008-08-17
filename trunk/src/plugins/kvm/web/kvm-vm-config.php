
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />

<?php

// error_reporting(E_ALL);
$thisfile = basename($_SERVER['PHP_SELF']);
$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
$BaseDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/';
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/deployment.class.php";
require_once "$RootDir/include/htmlobject.inc.php";
global $OPENQRM_SERVER_BASE_DIR;

$kvm_server_id = $_REQUEST["kvm_server_id"];
$kvm_server_name = $_REQUEST["kvm_server_name"];


function kvm_vm_config() {
	global $kvm_server_id;
	global $kvm_server_name;
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_USER;
	$refresh_delay=5;

	$kvm_server_appliance = new appliance();
	$kvm_server_appliance->get_instance_by_id($kvm_server_id);
	$kvm_server = new resource();
	$kvm_server->get_instance_by_id($kvm_server_appliance->resources);
	$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/kvm/bin/openqrm-kvm post_vm_config -n $kvm_server_name -u $OPENQRM_USER->name -p $OPENQRM_USER->password";
	$kvm_server->send_command($kvm_server->ip, $resource_command);
	sleep($refresh_delay);


	$disp = "<b>Kvm Configure VM</b>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";

	$vm_conf_ar=file("$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/kvm/web/kvm-stat/$kvm_server->id.$kvm_server_name.vm_config");
	foreach ($vm_conf_ar as $key => $value) {
	    $disp = $disp."Key: $key; Value: $value<br/>\n";
	}

	$disp = $disp."<br>";
	$disp = $disp."<b>Ram</b>";

	$disp = $disp."<br>";
	$disp = $disp."<b>Network</b>";

	$disp = $disp."<br>";
	$disp = $disp."<b>Disk</b>";

	$disp = $disp."<br>";
	$disp = $disp."<b>Vnc</b>";

	$disp = $disp."<br>";
	$disp = $disp."<br>";

	return $disp;
}



$output = array();
// if admin
if ($OPENQRM_USER->role == "administrator") {
	$output[] = array('label' => 'Kvm Configure VM', 'value' => kvm_vm_config());
}

echo htmlobject_tabmenu($output);

?>


