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
global $OPENQRM_SERVER_BASE_DIR;

// global event for logging
$event = new event();
global $event;


function wait_for_identfile($sfile) {
    $refresh_delay=1;
    $refresh_loop_max=20;
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

function get_image_rootdevice_identifier($zfs_storage_id) {
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_USER;
	global $event;

	// place for the storage stat files
	$StorageDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/plugins/zfs-storage/storage';
	$rootdevice_identifier_array = array();
	$storage = new storage();
	$storage->get_instance_by_id($zfs_storage_id);
	$storage_resource = new resource();
	$storage_resource->get_instance_by_id($storage->resource_id);
	$storage_resource_id = $storage_resource->id;
	$ident_file = "$StorageDir/$storage_resource_id.zfs.ident";
    if (file_exists($ident_file)) {
        unlink($ident_file);
    }
    // send command
	$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/zfs-storage/bin/openqrm-zfs-storage post_identifier -u $OPENQRM_USER->name -p $OPENQRM_USER->password";
	$storage_resource->send_command($storage_resource->ip, $resource_command);
    if (!wait_for_identfile($ident_file)) {
        $event->log("get_image_rootdevice_identifier", $_SERVER['REQUEST_TIME'], 2, "image.zfs-deployment", "Timeout while requesting image identifier from storage id $storage->id", "", "", 0, 0, 0);
        return;
    }
    $fcontent = file($ident_file);
    foreach($fcontent as $lun_info) {
        $tpos = strpos($lun_info, ",");
        $timage_name = trim(substr($lun_info, 0, $tpos));
        $troot_device = trim(substr($lun_info, $tpos+1));
        $rootdevice_identifier_array[] = array("value" => "$troot_device", "label" => "$timage_name");
    }
	return $rootdevice_identifier_array;
}

function get_image_default_rootfs() {
	return "ext3";
}

?>

