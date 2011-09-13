<html>
<head>
<title>openQRM Local-server actions</title>
</head>
<body>

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


$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/include/openqrm-server-config.php";
require_once "$RootDir/class/storage.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/deployment.class.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/include/htmlobject.inc.php";

global $IMAGE_INFO_TABLE;
global $DEPLOYMENT_INFO_TABLE;
global $KERNEL_INFO_TABLE;
global $STORAGETYPE_INFO_TABLE;
global $OPENQRM_SERVER_BASE_DIR;

// user/role authentication
if ($OPENQRM_USER->role != "administrator") {
	$event->log("authorization", $_SERVER['REQUEST_TIME'], 1, "local-server-action", "Un-Authorized access to lvm-actions from $OPENQRM_USER->name", "", "", 0, 0, 0);
	exit();
}


$local_server_command = htmlobject_request('local_server_command');
$local_server_id = htmlobject_request('local_server_id');
$local_server_root_device = htmlobject_request('local_server_root_device');
$local_server_root_device_type = htmlobject_request('local_server_root_device_type');
$local_server_kernel_version = htmlobject_request('local_server_kernel_version');
$local_server_name = htmlobject_request('local_server_name');

$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;

	$event->log("$local_server_command", $_SERVER['REQUEST_TIME'], 5, "local-server-action", "Processing local-server command $local_server_command", "", "", 0, 0, 0);
	switch ($local_server_command) {

		case 'integrate':

			// create storage server
			$storage_fields["storage_name"] = "resource$local_server_id";
			$storage_fields["storage_resource_id"] = "$local_server_id";
			$deployment = new deployment();
			$deployment->get_instance_by_type('local-server');
			$storage_fields["storage_type"] = $deployment->id;
			$storage_fields["storage_comment"] = "Local-server resource $local_server_id";
			$storage_fields["storage_capabilities"] = 'TYPE=local-server';
			$storage = new storage();
			$storage_fields["storage_id"]=openqrm_db_get_free_id('storage_id', $STORAGE_INFO_TABLE);
			$storage->add($storage_fields);

			// create image
			$image_fields["image_id"]=openqrm_db_get_free_id('image_id', $IMAGE_INFO_TABLE);
			$image_fields["image_name"] = "resource$local_server_id";
			$image_fields["image_type"] = $deployment->type;
			$image_fields["image_rootdevice"] = $local_server_root_device;
			$image_fields["image_rootfstype"] = $local_server_root_device_type;
			$image_fields["image_storageid"] = $storage_fields["storage_id"];
			$image_fields["image_comment"] = "Local-server image resource $local_server_id";
			$image_fields["image_capabilities"] = 'TYPE=local-server';
			$image = new image();
			$image->add($image_fields);

			// create kernel
			$kernel_fields["kernel_id"]=openqrm_db_get_free_id('kernel_id', $KERNEL_INFO_TABLE);
			$kernel_fields["kernel_name"]="resource$local_server_id";
			$kernel_fields["kernel_version"]="$local_server_kernel_version";
			$kernel_fields["kernel_capabilities"]='TYPE=local-server';
			$kernel = new kernel();
			$kernel->add($kernel_fields);

			// create appliance
			$next_appliance_id=openqrm_db_get_free_id('appliance_id', $APPLIANCE_INFO_TABLE);
			$appliance_fields["appliance_id"]=$next_appliance_id;
			$appliance_fields["appliance_name"]=$local_server_name;
			$appliance_fields["appliance_kernelid"]=$kernel_fields["kernel_id"];
			$appliance_fields["appliance_imageid"]=$image_fields["image_id"];
			$appliance_fields["appliance_resources"]="$local_server_id";
			$appliance_fields["appliance_capabilities"]='TYPE=local-server';
			$appliance_fields["appliance_comment"]="Local-server appliance resource $local_server_id";
			$appliance = new appliance();
			$appliance->add($appliance_fields);
			// set start time, reset stoptime, set state
			$now=$_SERVER['REQUEST_TIME'];
			$appliance_fields["appliance_starttime"]=$now;
			$appliance_fields["appliance_stoptime"]=0;
			$appliance_fields['appliance_state']='active';
			// set resource type to physical
			$appliance_fields['appliance_virtualization']=1;
			$appliance->update($next_appliance_id, $appliance_fields);

			// set resource to localboot
			$resource = new resource();
			$resource->get_instance_by_id($local_server_id);
			$openqrm_server->send_command("openqrm_server_set_boot local $local_server_id $resource->mac 0.0.0.0");
			$resource->set_localboot($local_server_id, 1);

			// update resource fields with kernel + image
			$kernel->get_instance_by_id($kernel_fields["kernel_id"]);
			$resource_fields["resource_kernel"]=$kernel->name;
			$resource_fields["resource_kernelid"]=$kernel_fields["kernel_id"];
			$image->get_instance_by_id($image_fields["image_id"]);
			$resource_fields["resource_image"]=$image->name;
			$resource_fields["resource_imageid"]=$image_fields["image_id"];
			// set capabilites
			$resource_fields["resource_capabilities"]='TYPE=local-server';
			$resource->update_info($local_server_id, $resource_fields);

			break;

		case 'remove':
			// remove all appliance with resource set to localserver id
			$appliance = new appliance();
			$appliance_id_list = $appliance->get_all_ids();
			foreach($appliance_id_list as $appliance_list) {
				$appliance_id = $appliance_list['appliance_id'];
				$app_resource_remove_check = new appliance();
				$app_resource_remove_check->get_instance_by_id($appliance_id);
				if ($app_resource_remove_check->resources == $local_server_id) {
					$appliance->remove($appliance_id);
				}
			}

			// remove kernel
			$kernel = new kernel();
			$kernel->remove_by_name("resource$local_server_id");
			// remove image
			$image = new image();
			$image->remove_by_name("resource$local_server_id");
			// remove storage serveer
			$storage = new storage();
			$storage->remove_by_name("resource$local_server_id");

			break;


		default:
			$event->log("$local_server_command", $_SERVER['REQUEST_TIME'], 3, "local-server-action", "No such local-server command ($local_server_command)", "", "", 0, 0, 0);
			break;


	}
?>

</body>
