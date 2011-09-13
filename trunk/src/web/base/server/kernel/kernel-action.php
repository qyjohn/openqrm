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
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/include/htmlobject.inc.php";

global $KERNEL_INFO_TABLE;

$event = new event();

// user/role authentication
if (!strstr($OPENQRM_USER->role, "administrator")) {
	$event->log("authorization", $_SERVER['REQUEST_TIME'], 1, "kernel-action", "Un-Authorized access to kernel-actions from $OPENQRM_USER->name", "", "", 0, 0, 0);
	exit();
}

$kernel_command=htmlobject_request('kernel_command');
$kernel_name=htmlobject_request('kernel_name');
?>
<html>
<head>
<title>openQRM Kernel actions</title>
<meta http-equiv="refresh" content="0; URL=kernel-overview.php?currenttab=tab0&strMsg=Processing <?php echo $kernel_command; ?> on <?php echo $kernel_name; ?>">
</head>
<body>
<?php


$kernel_id=htmlobject_request('kernel_id');
$kernel_name=htmlobject_request('kernel_name');
$kernel_version=htmlobject_request('kernel_version');
$kernel_fields = array();
foreach ($_REQUEST as $key => $value) {
	if (strncmp($key, "kernel_", 7) == 0) {
		$kernel_fields[$key] = $value;
	}
}
unset($kernel_fields["kernel_command"]);


$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();

global $OPENQRM_SERVER_IP_ADDRESS;

$event->log("$kernel_command", $_SERVER['REQUEST_TIME'], 5, "kernel-action", "Processing command $kernel_command for kernel $kernel_name", "", "", 0, 0, 0);
switch ($kernel_command) {
	case 'new_kernel':
		$kernel = new kernel();
		// check that name is unique
		$kernel->get_instance_by_name($kernel_name);
		if ($kernel->id > 0) {
			$event->log("$kernel_command", $_SERVER['REQUEST_TIME'], 5, "kernel-action", "Kernel name must be unique! Not adding new kernel ".$kernel_name, "", "", 0, 0, 0);
		} else {
			$kernel_fields["kernel_id"]=openqrm_db_get_free_id('kernel_id', $KERNEL_INFO_TABLE);
			$kernel->add($kernel_fields);
		}
		break;

	case 'update':
		$kernel = new kernel();
		$kernel->update($kernel_id, $kernel_fields);
		break;

	case 'remove':
		$kernel = new kernel();
		$kernel->remove($kernel_id);
		break;

	case 'remove_by_name':
		$kernel = new kernel();
		$kernel->remove_by_name($kernel_name);
		break;

	default:
		$event->log("$kernel_command", $_SERVER['REQUEST_TIME'], 3, "kernel-action", "No such kernel command ($kernel_command)", "", "", 0, 0, 0);
		break;


}
?>

</body>
