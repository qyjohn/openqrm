<?php
/**
 * @package openQRM
 */
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
$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/image_authentication.class.php";
require_once "$RootDir/class/storage.class.php";
require_once "$RootDir/class/deployment.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/include/openqrm-server-config.php";

/**
 * @package openQRM
 * @author Matt Rechenburg <mattr_sf@users.sourceforge.net>
 * @version 1.0
 */

global $OPENQRM_SERVER_BASE_DIR;
global $OPENQRM_EXEC_PORT;
global $IMAGE_AUTHENTICATION_TABLE;
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $openqrm_server;
$event = new event();
global $event;



	//--------------------------------------------------
	/**
	* authenticates the storage volume for the appliance resource
	* <code>
	* storage_auth_function("start", 2);
	* </code>
	* @access public
	*/
	//--------------------------------------------------
	function storage_auth_function($cmd, $appliance_id) {
		global $event;
		global $OPENQRM_SERVER_BASE_DIR;
		global $OPENQRM_SERVER_IP_ADDRESS;
		global $OPENQRM_EXEC_PORT;
		global $IMAGE_AUTHENTICATION_TABLE;
		global $openqrm_server;
		global $RootDir;

		$appliance = new appliance();
		$appliance->get_instance_by_id($appliance_id);

		$image = new image();
		$image->get_instance_by_id($appliance->imageid);
		$image_name=$image->name;
		$image_rootdevice=$image->rootdevice;

		$storage = new storage();
		$storage->get_instance_by_id($image->storageid);
		$storage_resource = new resource();
		$storage_resource->get_instance_by_id($storage->resource_id);
		$storage_ip = $storage_resource->ip;

		$deployment = new deployment();
		$deployment->get_instance_by_type($image->type);
		$deployment_type = $deployment->type;
		$deployment_plugin_name = $deployment->storagetype;

		$resource = new resource();
		$resource->get_instance_by_id($appliance->resources);
		$resource_mac=$resource->mac;
		$resource_ip=$resource->ip;

		// For xen-storage vms we assume that the image is located on the vm-host
		// so we send the auth command to the vm-host instead of the image storage.
		// This enables using a SAN backend with dedicated volumes per vm-host which all
		// contain all "golden-images" which are used for snapshotting.
		// We do this to overcome the current lvm limitation of not supporting cluster-wide snapshots
		$vm_host_resource = new resource();
		$vm_host_resource->get_instance_by_id($resource->vhostid);
		if ($vm_host_resource->id != $storage_resource->id) {
			$event->log("storage_auth_function", $_SERVER['REQUEST_TIME'], 5, "openqrm-xen-lvm-deployment-auth-hook.php", "Appliance $appliance_id image IS NOT available on this xen-storage host, $storage_resource->id not equal $vm_host_resource->id !! Assuming SAN Backend", "", "", 0, 0, $appliance_id);
		} else {
			$event->log("storage_auth_function", $_SERVER['REQUEST_TIME'], 5, "openqrm-xen-lvm-deployment-auth-hook.php", "Appliance $appliance_id image IS available on this xen-storage host, $storage_resource->id equal $vm_host_resource->id", "", "", 0, 0, $appliance_id);
		}

		switch($cmd) {
			case "start":
				// authenticate the rootfs / needs openqrm user + pass
				$openqrm_admin_user = new user("openqrm");
				$openqrm_admin_user->set_user();
				// generate a password for the image
				$event->log("storage_auth_function", $_SERVER['REQUEST_TIME'], 5, "openqrm-kvm-xen-deployment-auth-hook.php", "Authenticating $image_name / $image_location_name to resource $resource_mac", "", "", 0, 0, $appliance_id);
				$auth_start_cmd = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/$deployment_plugin_name/bin/openqrm-$deployment_plugin_name auth -n $image_name -r $image_rootdevice -i $image_name -u $openqrm_admin_user->name -p $openqrm_admin_user->password";
				$resource->send_command($vm_host_resource->ip, $auth_start_cmd);
				break;
		}

	}



	//--------------------------------------------------
	/**
	* de-authenticates the storage volume for the appliance resource
	* (runs via the image_authentication class)
	* <code>
	* storage_auth_stop(2);
	* </code>
	* @access public
	*/
	//--------------------------------------------------
	function storage_auth_stop($image_id) {

		global $event;
		global $OPENQRM_SERVER_BASE_DIR;
		global $OPENQRM_SERVER_IP_ADDRESS;
		global $OPENQRM_EXEC_PORT;

		$image = new image();
		$image->get_instance_by_id($image_id);
		$image_name=$image->name;
		$image_rootdevice=$image->rootdevice;

		$storage = new storage();
		$storage->get_instance_by_id($image->storageid);
		$storage_resource = new resource();
		$storage_resource->get_instance_by_id($storage->resource_id);
		$storage_ip = $storage_resource->ip;

		$deployment = new deployment();
		$deployment->get_instance_by_type($image->type);
		$deployment_type = $deployment->type;
		$deployment_plugin_name = $deployment->storagetype;


	}



	//--------------------------------------------------
	/**
	* de-authenticates the storage deployment volumes for the appliance resource
	* (runs via the image_authentication class)
	* <code>
	* storage_auth_deployment_stop(2);
	* </code>
	* @access public
	*/
	//--------------------------------------------------
	function storage_auth_deployment_stop($image_id) {

		global $event;
		global $OPENQRM_SERVER_BASE_DIR;
		global $OPENQRM_SERVER_IP_ADDRESS;
		global $OPENQRM_EXEC_PORT;

		$image = new image();
		$image->get_instance_by_id($image_id);
		$image_name=$image->name;
		$image_rootdevice=$image->rootdevice;

		$storage = new storage();
		$storage->get_instance_by_id($image->storageid);
		$storage_resource = new resource();
		$storage_resource->get_instance_by_id($storage->resource_id);
		$storage_ip = $storage_resource->ip;

		$deployment = new deployment();
		$deployment->get_instance_by_type($image->type);
		$deployment_type = $deployment->type;
		$deployment_plugin_name = $deployment->storagetype;


	}






?>


