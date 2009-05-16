<?php

// error_reporting(E_ALL);

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/include/openqrm-server-config.php";
require_once "$RootDir/class/storage.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
// special equallogic-storage classes
require_once "$RootDir/plugins/equallogic-storage/class/equallogic-storage-server.class.php";

global $OPENQRM_SERVER_BASE_DIR;
$EQUALLOGIC_STORAGE_SERVER_TABLE="equallogic_storage_servers";
global $EQUALLOGIC_STORAGE_SERVER_TABLE;
// global event for logging
$event = new event();
global $event;

function get_image_rootdevice_identifier($equallogic_storage_id) {
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_USER;
    global $EQUALLOGIC_STORAGE_SERVER_TABLE;
	global $event;
	$refresh_delay=5;

	// place for the storage stat files
	$StorageDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/plugins/equallogic-storage/storage';
	$rootdevice_identifier_array = array();
	$storage = new storage();
	$storage->get_instance_by_id($equallogic_storage_id);
	$storage_resource = new resource();
	$storage_resource->get_instance_by_id($storage->resource_id);
    // get the storage configuration
    $eq_storage = new equallogic_storage();
    $eq_storage->get_instance_by_storage_id($equallogic_storage_id);
    $eq_storage_ip = $storage_resource->ip;
    $eq_user = $eq_storage->storage_user;
    $eq_password = $eq_storage->storage_password;
	$openqrm_server_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/equallogic-storage/bin/openqrm-equallogic-storage post_identifier -u $eq_user -p $eq_password -e $eq_storage_ip";
    $output = shell_exec($openqrm_server_command);

    sleep($refresh_delay);
	$ident_file = "$StorageDir/$eq_storage_ip.equallogic.ident";
    $lun_loop=1;
	if (file_exists($ident_file)) {
		$fcontent = file($ident_file);
		foreach($fcontent as $lun_info) {
            $equallogic_output = trim($lun_info);
            $first_at_pos = strpos($equallogic_output, "@");
            $first_at_pos++;
            $eq_name = trim(substr($equallogic_output, 0, $first_at_pos-1));
			$rootdevice_identifier_array[] = array("value" => "/dev/$eq_storage_ip/$lun_loop", "label" => "$eq_name");
            $lun_loop++;
		}
	}
	return $rootdevice_identifier_array;

}

function get_image_default_rootfs() {
	return "ext3";
}

?>

