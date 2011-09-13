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
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/include/htmlobject.inc.php";

global $RESOURCE_INFO_TABLE;

$event = new event();

// user/role authentication
if (!strstr($OPENQRM_USER->role, "administrator")) {
	$event->log("authorization", $_SERVER['REQUEST_TIME'], 1, "resource-action", "Un-Authorized access to resource-actions from $OPENQRM_USER->name", "", "", 0, 0, 0);
	exit();
}

$resource_command = htmlobject_request('resource_command');
$resource_id = htmlobject_request('resource_id');
?>
<html>
<head>
<title>openQRM Resource actions</title>
<meta http-equiv="refresh" content="0; URL=resource-overview.php?currenttab=tab0&strMsg=Processing <?php echo $resource_command; ?> on <?php echo $resource_id; ?>">
</head>
<body>
<?php


$resource_hostname = htmlobject_request('resource_hostname');
$resource_mac = htmlobject_request('resource_mac');
$resource_ip = htmlobject_request('resource_ip');
$resource_state = htmlobject_request('resource_state');
$resource_event = htmlobject_request('resource_event');
foreach ($_REQUEST as $key => $value) {
	if (strncmp($key, "resource_", 9) == 0) {
		$resource_fields[$key] = $value;
	}
}
unset($resource_fields["resource_command"]);

$virtualization_id = htmlobject_request('virtualization_id');
$virtualization_name = htmlobject_request('virtualization_name');
$virtualization_type = htmlobject_request('virtualization_type');
$virtualization_fields = array();
foreach ($_REQUEST as $key => $value) {
	if (strncmp($key, "virtualization_", 15) == 0) {
		$virtualization_fields[$key] = $value;
	}
}


function res_new_redirect($strMsg) {
	global $thisfile;
	$url = 'resource-new.php?strMsg='.urlencode($strMsg).'&currenttab=tab0';
	echo "<meta http-equiv=\"refresh\" content=\"0; URL=$url\">";
	exit;
}


$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();

global $OPENQRM_SERVER_IP_ADDRESS;

$event->log("$resource_command", $_SERVER['REQUEST_TIME'], 5, "resource-action", "Processing command $resource_command on $resource_id", "", "", 0, 0, 0);
switch ($resource_command) {

	// new_resource needs :
	// resource_mac
	// resource_ip
	case 'new_resource':
		$strMsg = '';
		$resource = new resource();
		if ($resource->exists($resource_mac)) {
			$strMsg = "Resource ".$resource_mac." already exist in the openQRM-database!";
			res_new_redirect($strMsg);
		}
		if ("$resource_id" == "-1") {
			$new_resource_id=openqrm_db_get_free_id('resource_id', $RESOURCE_INFO_TABLE);
			$resource->id = $new_resource_id;
		} else {
		// 	check if resource_id is free
			if ($resource->is_id_free($resource_id)) {
				$new_resource_id=$resource_id;
			} else {
				$strMsg = "Given resource id ".$resource_id." is already in use!";
				res_new_redirect($strMsg);
			}
		}
		// check name
		if($resource_hostname != '') {
			if (!preg_match('#^[A-Za-z0-9_.-]*$#', $resource_hostname)) {
				$strMsg .= 'Hostname name must be [A-Za-z0-9_.-]<br/>';
				res_new_redirect($strMsg);
			}
		} else {
			$strMsg .= "Hostname can not be empty<br/>";
			res_new_redirect($strMsg);
		}

		// send command to the openQRM-server
		$openqrm_server->send_command("openqrm_server_add_resource $new_resource_id $resource_mac $resource_ip");
		// add to openQRM database
		$resource_fields["resource_id"]=$new_resource_id;
		$resource_fields["resource_localboot"]=0;
		$resource_fields["resource_vtype"]=1;
		$resource_fields["resource_vhostid"]=$new_resource_id;
		$resource->add($resource_fields);
		// set lastgood to -1 to prevent automatic checking the state
		$resource_fields["resource_lastgood"]=-1;
		$resource->update_info($new_resource_id, $resource_fields);
		// $resource->get_parameter($new_resource_id);

		break;

	// remove requires :
	// resource_id
	// resource_mac
	case 'remove':
		// remove from openQRM database
		$resource = new resource();
		$resource->remove($resource_id, $resource_mac);
		break;

	// localboot requires :
	// resource_id
	// resource_mac
	// resource_ip
	case 'localboot':
		$openqrm_server->send_command("openqrm_server_set_boot local $resource_id $resource_mac $resource_ip");
		// update db
		$resource = new resource();
		$resource->set_localboot($resource_id, 1);
		break;

	// netboot requires :
	// resource_id
	// resource_mac
	// resource_ip
	case 'netboot':
		$openqrm_server->send_command("openqrm_server_set_boot net $resource_id $resource_mac $resource_ip");
		// update db
		$resource = new resource();
		$resource->set_localboot($resource_id, 0);
		break;

	// assign requires :
	// resource_id
	// resource_mac
	// resource_ip
	// kernel_id
	// kernel_name
	// image_id
	// image_name
	// appliance_id

	case 'assign':

		$kernel_id=($_REQUEST["resource_kernelid"]);
		$kernel = new kernel();
		$kernel->get_instance_by_id($kernel_id);
		$kernel_name = $kernel->name;

		$image_id=($_REQUEST["resource_imageid"]);
		$image = new image();
		$image->get_instance_by_id($image_id);
		$image_name = $image->name;

		// send command to the openQRM-server
		$openqrm_server->send_command("openqrm_assign_kernel $resource_id $resource_mac $kernel_name");
		// update openQRM database
		$resource = new resource();
		$resource->assign($resource_id, $kernel_id, $kernel_name, $image_id, $image_name);
		$resource->send_command($resource_ip, "reboot");
		break;

	// reboot requires :
	// resource_ip
	case 'reboot':
		$resource = new resource();
		$resource->send_command("$resource_ip", "reboot");
		// set state to transition
		$resource_fields=array();
		$resource_fields["resource_state"]="transition";
		$resource = new resource();
		$resource->get_instance_by_ip($resource_ip);
		$resource->update_info($resource->id, $resource_fields);
		break;


	// halt requires :
	// resource_ip
	case 'halt':
		$resource = new resource();
		$resource->send_command("$resource_ip", "halt");
		// set state to off
		$resource_fields=array();
		$resource_fields["resource_state"]="off";
		$resource = new resource();
		$resource->get_instance_by_ip($resource_ip);
		$resource->update_info($resource->id, $resource_fields);
		break;

	// list requires :
	// nothing
	case 'list':
		$resource = new resource();
		$resource_list = $resource->get_resource_list();
		foreach ($resource_list as $resource_l) {
			foreach ($resource_l as $key => $val) {
				print "$key=$val ";
			}
			print "\n";
		}
		exit(0); // nothing more to do
		break;

	case 'add_virtualization_type':
		$virtualization = new virtualization();
		$virtualization_fields["virtualization_id"]=openqrm_db_get_free_id('virtualization_id', $VIRTUALIZATION_INFO_TABLE);
		$virtualization->add($virtualization_fields);
		break;

	case 'remove_virtualization_type':
		$virtualization = new virtualization();
		$virtualization->remove_by_type($virtualization_type);
		break;


	default:
		$event->log("$resource_command", $_SERVER['REQUEST_TIME'], 3, "resource-action", "No such resource command ($resource_command)", "", "", 0, 0, 0);
		break;
}

?>

</body>
