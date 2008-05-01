<?php

// This class represents the openQRM-server

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/event.class.php";

global $RESOURCE_INFO_TABLE;
$event = new event();
global $event;

class openqrm_server {

var $id = '';


// ---------------------------------------------------------------------------------
// general server methods
// ---------------------------------------------------------------------------------

// returns the ip of the openQRM-server
function get_ip_address() {
	global $RESOURCE_INFO_TABLE;
	global $event;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("select resource_openqrmserver from $RESOURCE_INFO_TABLE where resource_id=0");
	if (!$rs)
		$event->log("get_ip_address", $_SERVER['REQUEST_TIME'], 2, "openqrm_server.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
	else
	while (!$rs->EOF) {
		$resource_openqrmserver=$rs->fields["resource_openqrmserver"];
		$rs->MoveNext();
	}
	return $resource_openqrmserver;
}


// function to send a command to the openQRM-server
function send_command($server_command) {
	global $OPENQRM_EXEC_PORT;
	global $OPENQRM_SERVER_IP_ADDRESS;
	global $event;
	$fp = fsockopen($OPENQRM_SERVER_IP_ADDRESS, $OPENQRM_EXEC_PORT, $errno, $errstr, 30);
	if(!$fp) {
		$event->log("send_command", $_SERVER['REQUEST_TIME'], 2, "openqrm_server.class.php", "Could not connect to the openQRM-Server", "", "", 0, 0, 0);
		$event->log("send_command", $_SERVER['REQUEST_TIME'], 2, "openqrm_server.class.php", "$errstr ($errno)", "", "", 0, 0, 0);
		return false;
	} else {
		fputs($fp,"$server_command");
		fclose($fp);
		return true;
	}
}



// ---------------------------------------------------------------------------------

}

?>