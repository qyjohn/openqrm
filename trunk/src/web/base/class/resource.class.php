<?php

// This class represents a resource in openQRM (physical hardware or virtual machine)


$RootDir = $_SERVER["DOCUMENT_ROOT"].'openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/openqrm_server.class.php";

global $RESOURCE_INFO_TABLE;


class resource {

var $id = '';
var $localboot = '';
var $kernel = '';
var $kernelid = '';
var $image = '';
var $imageid = '';
var $openqrmserver = '';
var $basedir = '';
var $serverid = '';
var $ip = '';
var $subnet = '';
var $broadcast = '';
var $network = '';
var $mac = '';
var $uptime = '';
var $cpunumber = '';
var $cpuspeed = '';
var $cpumodel = '';
var $memtotal = '';
var $memused = '';
var $swaptotal = '';
var $swapused = '';
var $hostname = '';
var $load = '';
var $execdport = '';
var $senddelay = '';
var $capabilities = '';
var $state = '';
var $event = '';

// ---------------------------------------------------------------------------------
// methods to create an instance of a resource object filled from the db
// ---------------------------------------------------------------------------------

// returns a resource from the db selected by id, mac or ip
function get_instance($id, $mac, $ip) {
	global $RESOURCE_INFO_TABLE;
	$db=openqrm_get_db_connection();
	if ("$id" != "") {
		$resource_array = $db->GetAll("select * from $RESOURCE_INFO_TABLE where resource_id=$id");
	} else if ("$mac" != "") {
		$resource_array = $db->GetAll("select * from $RESOURCE_INFO_TABLE where resource_mac=$mac");
	} else if ("$ip" != "") {
		$resource_array = $db->GetAll("select * from $RESOURCE_INFO_TABLE where resource_ip=$ip");
	} else {
		echo "ERROR: Could not create instance of resource without data";
		exit(-1);
	}
	foreach ($resource_array as $index => $resource) {
		$this->id = $resource["resource_id"];
		$this->localboot = $resource["resource_localboot"];
		$this->kernel = $resource["resource_kernel"];
		$this->kernelid = $resource["resource_kernelid"];
		$this->image = $resource["resource_image"];
		$this->imageid = $resource["resource_imageid"];
		$this->openqrmserver = $resource["resource_openqrmserver"];
		$this->basedir = $resource["resource_basedir"];
		$this->serverid = $resource["resource_serverid"];
		$this->ip = $resource["resource_ip"];
		$this->subnet = $resource["resource_subnet"];
		$this->broadcast = $resource["resource_broadcast"];
		$this->network = $resource["resource_network"];
		$this->mac = $resource["resource_mac"];
		$this->uptime = $resource["resource_uptime"];
		$this->cpunumber = $resource["resource_cpunumber"];
		$this->cpuspeed = $resource["resource_cpuspeed"];
		$this->cpumodel = $resource["resource_cpumodel"];
		$this->memtotal = $resource["resource_memtotal"];
		$this->memused = $resource["resource_memused"];
		$this->swaptotal = $resource["resource_swaptotal"];
		$this->swapused = $resource["resource_swapused"];
		$this->hostname = $resource["resource_hostname"];
		$this->load = $resource["resource_load"];
		$this->execdport = $resource["resource_execdport"];
		$this->senddelay = $resource["resource_senddelay"];
		$this->capabilities = $resource["resource_capabilities"];
		$this->state = $resource["resource_state"];
		$this->event = $resource["resource_evemnt"];
	}
	return $this;
}

// returns a resource from the db selected by id
function get_instance_by_id($id) {
	$this->get_instance($id, "", "");
	return $this;
}

// returns a resource from the db selected by ip
function get_instance_by_ip($ip) {
	$this->get_instance("", "", $ip);
	return $this;
}

// returns a resource from the db selected by mac
function get_instance_by_mac($mac) {
	$this->get_instance("", $mac, "");
	return $this;
}



// ---------------------------------------------------------------------------------
// getter + setter
// ---------------------------------------------------------------------------------

function get_id() {
	return $this->id;
}

function set_id($id) {
	$this->id = $id;
}



// ---------------------------------------------------------------------------------
// general resource methods
// ---------------------------------------------------------------------------------

// checks if a resource exists in the database
function exists($mac_address) {
	global $RESOURCE_INFO_TABLE;
	$db=openqrm_get_db_connection();
	$rs = &$db->Execute("select resource_id from $RESOURCE_INFO_TABLE where resource_mac='$mac_address'");
	if ($rs->EOF) {
		return false;
	} else {
		return true;
	}
}


// returns the next free resource-id
function get_next_id() {
	global $RESOURCE_INFO_TABLE;
	$next_free_resource_id=0;
	$db=openqrm_get_db_connection();
	$recordSet = &$db->Execute("select resource_id from $RESOURCE_INFO_TABLE");
	if (!$recordSet)
        print $db->ErrorMsg();
    else
	while (!$recordSet->EOF) {
		if ($recordSet->fields["resource_id"] != $next_free_resource_id) {
			if (openqrm_is_resource_id_free($next_free_resource_id)) {
				return $next_free_resource_id;
			}
		}
		$next_free_resource_id++;
		$recordSet->MoveNext();
	}
    $recordSet->Close();
    $db->Close();
    return $next_free_resource_id;
}


// checks if given resource id is free in the db
function is_id_free($resource_id) {
	global $RESOURCE_INFO_TABLE;
	$db=openqrm_get_db_connection();
	$rs = &$db->Execute("select resource_id from $RESOURCE_INFO_TABLE where resource_id=$resource_id");
	if (!$rs)
		print $db->ErrorMsg();
	else
	if ($rs->EOF) {
		return true;
	} else {
		return false;
	}
}



// adds resource to the database
function add($resource_id, $resource_mac, $resource_ip) {
	global $OPENQRM_EXEC_PORT;
	global $RESOURCE_INFO_TABLE;
	global $OPENQRM_RESOURCE_BASE_DIR;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("insert into $RESOURCE_INFO_TABLE (resource_id, resource_localboot, resource_kernel, resource_kernelid, resource_image, resource_imageid, resource_openqrmserver, resource_basedir, resource_serverid, resource_ip, resource_subnet, resource_broadcast, resource_network, resource_mac, resource_uptime, resource_cpunumber, resource_cpuspeed, resource_cpumodel, resource_memtotal, resource_memused, resource_swaptotal, resource_swapused, resource_hostname, resource_load, resource_execdport, resource_senddelay, resource_state, resource_event) values ($resource_id, 0, 'default', 1, 'idle', 1, '$OPENQRM_SERVER_IP_ADDRESS', '$OPENQRM_RESOURCE_BASE_DIR', 1, '$resource_ip', '', '', '', '$resource_mac', 0, 0, 0, '0', 0, 0, 0, 0, 'idle', 0, $OPENQRM_EXEC_PORT, 30, 'booting', 'detected')");
}

// removes resource from the database
function remove($resource_id, $resource_mac) {
	global $RESOURCE_INFO_TABLE;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("delete from $RESOURCE_INFO_TABLE where resource_id=$resource_id and resource_mac='$resource_mac'");
}


// assigns a kernel and fs-image to a resource
function assign($resource_id, $resource_kernel, $resource_kernelid, $resource_image, $resource_imageid, $resource_serverid) {
	global $RESOURCE_INFO_TABLE;
	$db=openqrm_get_db_connection();
	if ("$resource_imageid" == "1") {
		// idle
		$rs = $db->Execute("update $RESOURCE_INFO_TABLE set
			 resource_kernel='$resource_kernel',
			 resource_kernelid=$resource_kernelid,
			 resource_image='$resource_image',
			 resource_imageid=$resource_imageid,
			 resource_serverid=1 where resource_id=$resource_id");
	} else {
		$rs = $db->Execute("update $RESOURCE_INFO_TABLE set
			resource_kernel='$resource_kernel',
			resource_kernelid=$resource_kernelid,
			resource_image='$resource_image',
			resource_imageid=$resource_imageid,
			resource_serverid=$resource_serverid where resource_id=$resource_id");
	}
}



// set a resource to net- or local boot
// resource_localboot = 0 -> netboot / 1 -> localboot
function set_localboot($resource_id, $resource_localboot) {
	global $RESOURCE_INFO_TABLE;
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("update $RESOURCE_INFO_TABLE set resource_localboot=$resource_localboot where resource_id=$resource_id");
}


// displays resource parameter for resource_id
function get_parameter($resource_id) {
	global $RESOURCE_INFO_TABLE;
	global $KERNEL_INFO_TABLE;
	global $IMAGE_INFO_TABLE;
	$db=openqrm_get_db_connection();
	// resource parameter
	$recordSet = &$db->Execute("select resource_id, resource_localboot, resource_kernel, resource_kernelid, resource_image, resource_imageid, resource_openqrmserver, resource_basedir, resource_serverid, resource_ip, resource_subnet, resource_broadcast, resource_network, resource_mac, resource_execdport, resource_senddelay from $RESOURCE_INFO_TABLE where resource_id=$resource_id");
	if (!$recordSet)
		print $db->ErrorMsg();
	else
	while (!$recordSet->EOF) {
		array_walk($recordSet->fields, 'print_array');
		$image_id=$recordSet->fields["resource_imageid"];
		$kernel_id=$recordSet->fields["resource_kernelid"];
		$recordSet->MoveNext();
	}
	$recordSet->Close();
	// kernel-parameter
/*	$recordSet = &$db->Execute("select * from $KERNEL_INFO_TABLE where kernel_id=$kernel_id");
	if (!$recordSet)
		print $db->ErrorMsg();
	else
	while (!$recordSet->EOF) {
		array_walk($recordSet->fields, 'print_array');
		$recordSet->MoveNext();
	}
	$recordSet->Close();
	// image-parameter
	$recordSet = &$db->Execute("select * from $IMAGE_INFO_TABLE where image_id=$image_id");
	if (!$recordSet)
		print $db->ErrorMsg();
	else
	while (!$recordSet->EOF) {
		array_walk($recordSet->fields, 'print_array');
		$recordSet->MoveNext();
	}
	$recordSet->Close();
	$db->Close();
*/

	// enabled plugins
	// TODO


}

function get_parameter_array($resource_id) {
	global $RESOURCE_INFO_TABLE;
    $db = openqrm_get_db_connection();
	$resource_array = $db->GetAll("select * from $RESOURCE_INFO_TABLE where resource_id=$resource_id");
	return $resource_array;
}

function get_list() {
	global $RESOURCE_INFO_TABLE;
	$resource_list = array();
	$db=openqrm_get_db_connection();
	$rs = $db->Execute("select resource_id, resource_ip, resource_state from $RESOURCE_INFO_TABLE");
	if (!$rs)
		print $db->ErrorMsg();
	else
	while (!$rs->EOF) {
		$resource_list[] = $rs->fields;
		$rs->MoveNext();
	}
	return $resource_list;
}



function update_info($resource_id, $resource_fields) {
	global $RESOURCE_INFO_TABLE;
	if (! is_array($resource_fields)) {
		print("ERROR: Unable to update resource $resource_id");
		return 1;
	}
	$db=openqrm_get_db_connection();
	unset($resource_fields["resource_id"]);
	$result = $db->AutoExecute($RESOURCE_INFO_TABLE, $resource_fields, 'UPDATE', "resource_id = $resource_id");
	if (! $result) {
		print("Failed updating resource $resource_id");
	}

	//$resource_uptime, $resource_cpu_number, $resource_cpu_speed, $resource_cpu_model, $resource_mem_total, $resource_mem_used, $resource_swap_total, $resource_swap_used, $resource_hostname, $resource_cpu_load, $resource_state, $resource_event

}

function update_status($resource_id, $resource_state, $resource_event) {
	global $RESOURCE_INFO_TABLE;
	$db=openqrm_get_db_connection();
	$query = "update $RESOURCE_INFO_TABLE set
			resource_state='$resource_state',
			resource_event='$resource_event'
			where resource_id=$resource_id";
	$rs = $db->Execute("$query");
}



// function to send a command to a resource by resource_ip
function send_command($resource_ip, $resource_command) {
	global $OPENQRM_EXEC_PORT;
	$fp = fsockopen($resource_ip, $OPENQRM_EXEC_PORT, $errno, $errstr, 30);
	if(!$fp) {
		echo "ERROR: Could not send the command to resource $resource_ip<br>";
		echo "ERROR: $errstr ($errno)<br>";
		exit();
	}
	fputs($fp,"$resource_command");
	fclose($fp);
}



// returns the number of managed resource
function get_count($which) {
	global $RESOURCE_INFO_TABLE;
	$count = 0;
	$db=openqrm_get_db_connection();

    $sql = "select count(resource_id) as num from $RESOURCE_INFO_TABLE where resource_id!=0";
	switch($which) {
		case 'all':
			break;
		case 'online':
			$sql .= " and resource_state='active'";
			break;
		case 'offline':
			$sql .= " and resource_state!='active'";
			break;
	}
	$rs = $db->Execute($sql);
	if (!$rs) {
		print $db->ErrorMsg();
	} else {
		$count = $rs->fields["num"];
	}
	return $count;
}


// displays the resource-overview
function display_overview($start, $count) {
	global $RESOURCE_INFO_TABLE;
	$db=openqrm_get_db_connection();
	$recordSet = &$db->SelectLimit("select * from $RESOURCE_INFO_TABLE where resource_id>=$start order by resource_id ASC", $count);
	$resource_array = array();
	if (!$recordSet) {
		print $db->ErrorMsg();
	} else {
		while (!$recordSet->EOF) {
			array_push($resource_array, $recordSet->fields);
			$recordSet->MoveNext();
		}
		$recordSet->Close();
	}		
	return $resource_array;
}





// ---------------------------------------------------------------------------------

}

?>
