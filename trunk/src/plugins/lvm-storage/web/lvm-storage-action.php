<?php
$lvm_storage_command = $_REQUEST["lvm_storage_command"];
$lvm_storage_id = $_REQUEST["lvm_storage_id"];
$lvm_volume_group = $_REQUEST["lvm_volume_group"];
?>

<html>
<head>
<title>openQRM Lvm-storage actions</title>
<meta http-equiv="refresh" content="0; URL=lvm-storage-manager.php?currenttab=tab1&strMsg=Processing <?php echo $lvm_storage_command; ?> on storage <?php echo $lvm_storage_id; ?>">
</head>
<body>

<?php

$RootDir = $_SERVER["DOCUMENT_ROOT"].'openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/include/openqrm-server-config.php";
require_once "$RootDir/class/storage.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/deployment.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
global $IMAGE_INFO_TABLE;
global $DEPLOYMENT_INFO_TABLE;
global $OPENQRM_SERVER_BASE_DIR;

// place for the storage stat files
$StorageDir = $_SERVER["DOCUMENT_ROOT"].'openqrm/base/plugins/9/storage';

// user/role authentication
if ($OPENQRM_USER->role != "administrator") {
	syslog(LOG_ERR, "openQRM-engine: Un-Authorized access to lvm-storage-actions from $OPENQRM_USER->name!");
	exit();
}

$lvm_storage_name = $_REQUEST["lvm_storage_name"];
$lvm_storage_logcial_volume_size = $_REQUEST["lvm_storage_logcial_volume_size"];
$lvm_storage_logcial_volume_name = $_REQUEST["lvm_storage_logcial_volume_name"];
$lvm_storage_logcial_volume_snapshot_name = $_REQUEST["lvm_storage_logcial_volume_snapshot_name"];
$lvm_storage_type = $_REQUEST["lvm_storage_type"];
$lvm_storage_fields = array();
foreach ($_REQUEST as $key => $value) {
	if (strncmp($key, "lvm_storage_", 11) == 0) {
		$lvm_storage_fields[$key] = $value;
	}
}

unset($lvm_storage_fields["lvm_storage_command"]);

	syslog(LOG_NOTICE, "openQRM-engine: Processing command $lvm_storage_command on Image $lvm_storage_name");
	switch ($lvm_storage_command) {
		case 'get_storage':
			if (!file_exists($StorageDir)) {
				mkdir($StorageDir);
			}
			$filename = $StorageDir."/".$_POST['filename'];
			$filedata = base64_decode($_POST['filedata']);
			echo "<h1>$filename</h1>";
			$fout = fopen($filename,"wb");
			fwrite($fout, $filedata);
			fclose($fout);
			break;

		case 'refresh_vg':
			$storage = new storage();
			$storage->get_instance_by_id($lvm_storage_id);
			$storage_resource = new resource();
			$storage_resource->get_instance_by_id($storage->resource_id);
			$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/lvm-storage/bin/openqrm-lvm-storage post_vg -u $OPENQRM_USER->name -p $OPENQRM_USER->password";
			$storage_resource->send_command($storage_resource->ip, $resource_command);
			break;

		case 'refresh_lv':
			$storage = new storage();
			$storage->get_instance_by_id($lvm_storage_id);
			$storage_resource = new resource();
			$storage_resource->get_instance_by_id($storage->resource_id);
			$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/lvm-storage/bin/openqrm-lvm-storage post_lv -u $OPENQRM_USER->name -p $OPENQRM_USER->password -v $lvm_volume_group";
			$storage_resource->send_command($storage_resource->ip, $resource_command);
			break;

		case 'add_lv':
			$storage = new storage();
			$storage->get_instance_by_id($lvm_storage_id);
			$storage_resource = new resource();
			$storage_resource->get_instance_by_id($storage->resource_id);
			$storage_deployment = new deployment();
			$storage_deployment->get_instance_by_id($storage->deployment_type);
			$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/lvm-storage/bin/openqrm-lvm-storage add -n $lvm_storage_logcial_volume_name -v $lvm_volume_group -t $storage_deployment->type -m $lvm_storage_logcial_volume_size";
			$storage_resource->send_command($storage_resource->ip, $resource_command);
			break;

		case 'remove_lv':
			$storage = new storage();
			$storage->get_instance_by_id($lvm_storage_id);
			$storage_resource = new resource();
			$storage_resource->get_instance_by_id($storage->resource_id);
			$storage_deployment = new deployment();
			$storage_deployment->get_instance_by_id($storage->deployment_type);
			$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/lvm-storage/bin/openqrm-lvm-storage remove -n $lvm_storage_logcial_volume_name -v $lvm_volume_group -t $storage_deployment->type";
			$storage_resource->send_command($storage_resource->ip, $resource_command);
			break;

		case 'snap_lv':
			$storage = new storage();
			$storage->get_instance_by_id($lvm_storage_id);
			$storage_resource = new resource();
			$storage_resource->get_instance_by_id($storage->resource_id);
			$storage_deployment = new deployment();
			$storage_deployment->get_instance_by_id($storage->deployment_type);
			$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/lvm-storage/bin/openqrm-lvm-storage snap -n $lvm_storage_logcial_volume_name -v $lvm_volume_group -t $storage_deployment->type -s $lvm_storage_logcial_volume_snapshot_name -m $lvm_storage_logcial_volume_size";
			$storage_resource->send_command($storage_resource->ip, $resource_command);
			break;
		default:
			echo "No Such openQRM-command!";
			break;


	}
?>

</body>