<?php
/**
 * @package openQRM
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

        // this is a hook for the cloud-plugin to be able
        // to translate the internal to the external ip address
        // for the nfs-mount authentication
/*        if (file_exists("$RootDir/plugins/cloud/.running")) {
            $event->log("storage_auth_function", $_SERVER['REQUEST_TIME'], 5, "openqrm-lvm-nfs-deployment-auth-hook.php", "Found Cloud enabled and running. Checking for CloudNAT", "", "", 0, 0, $appliance_id);
            // special clouduser class
            require_once "$RootDir/plugins/cloud/class/cloudconfig.class.php";
            require_once "$RootDir/plugins/cloud/class/cloudnat.class.php";
            // check if we have to cloudnat the ip address
            $cn_conf = new cloudconfig();
            $cn_nat_enabled = $cn_conf->get_value(18);  // 18 is cloud_nat
            if (!strcmp($cn_nat_enabled, "true")) {
                $cn = new cloudnat();
                $internal_resource_ip=$resource_ip;
                $resource_ip = $cn->translate($resource_ip);
                $event->log("storage_auth_function", $_SERVER['REQUEST_TIME'], 5, "openqrm-lvm-nfs-deployment-auth-hook.php", "Found CloudNAT enabled, translated $internal_resource_ip to $resource_ip", "", "", 0, 0, $appliance_id);
            } else {
                $event->log("storage_auth_function", $_SERVER['REQUEST_TIME'], 5, "openqrm-lvm-nfs-deployment-auth-hook.php", "Cloudnat is disabled, keeping $resource_ip", "", "", 0, 0, $appliance_id);
            }
        } else {
			$event->log("storage_auth_function", $_SERVER['REQUEST_TIME'], 5, "openqrm-lvm-nfs-deployment-auth-hook.php", "Cloud is not enabled/running. Not checking for CloudNAT", "", "", 0, 0, $appliance_id);

        }
*/

		switch($cmd) {
			case "start":
				// authenticate the rootfs
				$event->log("storage_auth_function", $_SERVER['REQUEST_TIME'], 5, "openqrm-lvm-nfs-deployment-auth-hook.php", "Authenticating $image_name / $image_rootdevice to resource $resource_ip", "", "", 0, 0, $appliance_id);
				$auth_start_cmd = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/$deployment_plugin_name/bin/openqrm-$deployment_plugin_name auth -r $image_rootdevice -i $resource_ip -t lvm-nfs-deployment";
				$resource->send_command($storage_ip, $auth_start_cmd);
	 
	 			// authenticate the install-from-nfs export
				$run_disable_deployment_export=0;
				$install_from_nfs_param = trim($image->get_deployment_parameter("IMAGE_INSTALL_FROM_NFS"));
				if (strlen($install_from_nfs_param)) {
	
					// storage -> resource -> auth
					$ip_storage_id=$deployment->parse_deployment_parameter("id", $install_from_nfs_param);
					$ip_storage_ip=$deployment->parse_deployment_parameter("ip", $install_from_nfs_param);
					$ip_image_rootdevice=$deployment->parse_deployment_parameter("path", $install_from_nfs_param);
	
					$ip_storage = new storage();
					$ip_storage->get_instance_by_id($ip_storage_id);
					$ip_storage_resource = new resource();
					$ip_storage_resource->get_instance_by_id($ip_storage->resource_id);
					$op_storage_ip = $ip_storage_resource->ip;
	
					$ip_deployment = new deployment();
					$ip_deployment->get_instance_by_id($ip_storage->type);
					$ip_deployment_type = $ip_deployment->type;
					$ip_deployment_plugin_name = $ip_deployment->storagetype;
	
					$event->log("storage_auth_function", $_SERVER['REQUEST_TIME'], 5, "openqrm-lvm-nfs-deployment-auth-hook.php", "Install-from-NFS: Authenticating $resource_ip on storage id $ip_storage_id:$ip_storage_ip:$ip_image_rootdevice", "", "", 0, 0, $appliance_id);
					$auth_install_from_nfs_start_cmd = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/$ip_deployment_plugin_name/bin/openqrm-$ip_deployment_plugin_name auth -r $ip_image_rootdevice -i $resource_ip -t $ip_deployment_type";
					$resource->send_command($ip_storage_ip, $auth_install_from_nfs_start_cmd);
	
					$run_disable_deployment_export=1;
				}
	
	 			// authenticate the transfer-to-nfs export
				$transfer_from_nfs_param = trim($image->get_deployment_parameter("IMAGE_TRANSFER_TO_NFS"));
				if (strlen($transfer_from_nfs_param)) {
					// storage -> resource -> auth
					$tp_storage_id=$deployment->parse_deployment_parameter("id", $transfer_from_nfs_param);
					$tp_storage_ip=$deployment->parse_deployment_parameter("ip", $transfer_from_nfs_param);
					$tp_image_rootdevice=$deployment->parse_deployment_parameter("path", $transfer_from_nfs_param);
	
					$tp_storage = new storage();
					$tp_storage->get_instance_by_id($tp_storage_id);
					$tp_storage_resource = new resource();
					$tp_storage_resource->get_instance_by_id($tp_storage->resource_id);
					$op_storage_ip = $tp_storage_resource->ip;
	
					$tp_deployment = new deployment();
					$tp_deployment->get_instance_by_id($tp_storage->type);
					$tp_deployment_type = $tp_deployment->type;
					$tp_deployment_plugin_name = $tp_deployment->storagetype;
	
					$event->log("storage_auth_function", $_SERVER['REQUEST_TIME'], 5, "openqrm-lvm-nfs-deployment-auth-hook.php", "Transfer-to-NFS: Authenticating $resource_ip on storage id $tp_storage_id:$tp_storage_ip:$tp_image_rootdevice", "", "", 0, 0, $appliance_id);
					$auth_install_from_nfs_start_cmd = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/$tp_deployment_plugin_name/bin/openqrm-$tp_deployment_plugin_name auth -r $tp_image_rootdevice -i $resource_ip -t $tp_deployment_type";
					$resource->send_command($tp_storage_ip, $auth_install_from_nfs_start_cmd);
	
					$run_disable_deployment_export=1;
				}
	
				// do we need to disable the install-from/transfer-to-nfs exports ?
				if ($run_disable_deployment_export == 1) {
					$image_authentication = new image_authentication();
					$ia_id = openqrm_db_get_free_id('ia_id', $IMAGE_AUTHENTICATION_TABLE);
					$image_auth_ar = array(
						'ia_id' => $ia_id,
						'ia_image_id' => $appliance->imageid,
						'ia_resource_id' => $appliance->resources,
						'ia_auth_type' => 1,
					);
					$image_authentication->add($image_auth_ar);
					$event->log("storage_auth_function", $_SERVER['REQUEST_TIME'], 5, "openqrm-lvm-nfs-deployment-auth-hook.php", "Registered image $appliance->imageid for de-authentication the deployment exports when resource $appliance->resources is fully up.", "", "", 0, 0, $appliance_id);
				}
				
	
	
				break;
			case "stop":
				$image_authentication = new image_authentication();
				$ia_id = openqrm_db_get_free_id('ia_id', $IMAGE_AUTHENTICATION_TABLE);
				$image_auth_ar = array(
					'ia_id' => $ia_id,
					'ia_image_id' => $appliance->imageid,
					'ia_resource_id' => $appliance->resources,
					'ia_auth_type' => 0,
				);
				$image_authentication->add($image_auth_ar);
				$event->log("storage_auth_function", $_SERVER['REQUEST_TIME'], 5, "openqrm-lvm-nfs-deployment-auth-hook.php", "Registered image $appliance->imageid for de-authentication the root-fs exports when resource $appliance->resources is idle again.", "", "", 0, 0, $appliance_id);
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
	
		$auth_stop_cmd = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/$deployment_plugin_name/bin/openqrm-$deployment_plugin_name auth -r $image_rootdevice -i $OPENQRM_SERVER_IP_ADDRESS -t lvm-nfs-deployment";
		$resource = new resource();
		$resource->send_command($storage_ip, $auth_stop_cmd);
	
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

		// just for sending the commands	
		$resource = new resource();

		// get install deployment params
		$install_from_nfs_param = trim($image->get_deployment_parameter("IMAGE_INSTALL_FROM_NFS"));
		if (strlen($install_from_nfs_param)) {
			// storage -> resource -> auth
			$ip_storage_id=$deployment->parse_deployment_parameter("id", $install_from_nfs_param);
			$ip_storage_ip=$deployment->parse_deployment_parameter("ip", $install_from_nfs_param);
			$ip_image_rootdevice=$deployment->parse_deployment_parameter("path", $install_from_nfs_param);
	
			$ip_storage = new storage();
			$ip_storage->get_instance_by_id($ip_storage_id);
			$ip_storage_resource = new resource();
			$ip_storage_resource->get_instance_by_id($ip_storage->resource_id);
			$op_storage_ip = $ip_storage_resource->ip;
	
			$ip_deployment = new deployment();
			$ip_deployment->get_instance_by_id($ip_storage->type);
			$ip_deployment_type = $ip_deployment->type;
			$ip_deployment_plugin_name = $ip_deployment->storagetype;
	
			$event->log("storage_auth_function", $_SERVER['REQUEST_TIME'], 5, "openqrm-lvm-nfs-deployment-auth-hook.php", "Install-from-NFS: Authenticating $resource_ip on storage id $ip_storage_id:$ip_storage_ip:$ip_image_rootdevice", "", "", 0, 0, $resource_id);
			$auth_install_from_nfs_start_cmd = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/$ip_deployment_plugin_name/bin/openqrm-$ip_deployment_plugin_name auth -r $ip_image_rootdevice -i $OPENQRM_SERVER_IP_ADDRESS -t $ip_deployment_type";
			$resource->send_command($ip_storage_ip, $auth_install_from_nfs_start_cmd);
		}
	
		// get transfer deployment params
		$transfer_from_nfs_param = trim($image->get_deployment_parameter("IMAGE_TRANSFER_TO_NFS"));
		if (strlen($transfer_from_nfs_param)) {
			// storage -> resource -> auth
			$tp_storage_id=$deployment->parse_deployment_parameter("id", $transfer_from_nfs_param);
			$tp_storage_ip=$deployment->parse_deployment_parameter("ip", $transfer_from_nfs_param);
			$tp_image_rootdevice=$deployment->parse_deployment_parameter("path", $transfer_from_nfs_param);
	
			$tp_storage = new storage();
			$tp_storage->get_instance_by_id($tp_storage_id);
			$tp_storage_resource = new resource();
			$tp_storage_resource->get_instance_by_id($tp_storage->resource_id);
			$op_storage_ip = $tp_storage_resource->ip;
	
			$tp_deployment = new deployment();
			$tp_deployment->get_instance_by_id($tp_storage->type);
			$tp_deployment_type = $tp_deployment->type;
			$tp_deployment_plugin_name = $tp_deployment->storagetype;
	
			$event->log("storage_auth_function", $_SERVER['REQUEST_TIME'], 5, "openqrm-lvm-nfs-deployment-auth-hook.php", "Install-from-NFS: Authenticating $resource_ip on storage id $tp_storage_id:$tp_storage_ip:$tp_image_rootdevice", "", "", 0, 0, $resource_id);
			$auth_install_from_nfs_start_cmd = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/$tp_deployment_plugin_name/bin/openqrm-$tp_deployment_plugin_name auth -r $tp_image_rootdevice -i $OPENQRM_SERVER_IP_ADDRESS -t $tp_deployment_type";
			$resource->send_command($tp_storage_ip, $auth_install_from_nfs_start_cmd);
		}
	
	}






?>


