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


// This class represents a cloud user in openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";

$CLOUD_USER_TABLE="cloud_users";
global $CLOUD_USER_TABLE;
$event = new event();
global $event;

class clouduser {

	var $id = '';
	var $cg_id = '';
	var $name = '';
	var $password = '';
	var $lastname = '';
	var $forename = '';
	var $email = '';
	var $street = '';
	var $city = '';
	var $country = '';
	var $phone = '';
	var $status = '';
	var $ccunits = '';
	var $token = '';

	//--------------------------------------------------
	/**
	* Constructor
	*/
	//--------------------------------------------------
	function clouduser() {
		$this->init();
	}

	//--------------------------------------------------
	/**
	* init storage environment
	* @access public
	*/
	//--------------------------------------------------
	function init() {
		global $CLOUD_USER_TABLE, $OPENQRM_SERVER_BASE_DIR;
		$this->_event = new event();
		$this->_db_table = $CLOUD_USER_TABLE;
		$this->_base_dir = $OPENQRM_SERVER_BASE_DIR;
	}




// ---------------------------------------------------------------------------------
// methods to create an instance of a clouduser object filled from the db
// ---------------------------------------------------------------------------------

	// returns an appliance from the db selected by id or name
	function get_instance($id, $name) {
		global $CLOUD_USER_TABLE;
		global $event;
		$db=openqrm_get_db_connection();
		if ("$id" != "") {
			$clouduser_array = &$db->Execute("select * from $CLOUD_USER_TABLE where cu_id=$id");
		} else if ("$name" != "") {
			$clouduser_array = &$db->Execute("select * from $CLOUD_USER_TABLE where cu_name='$name'");
		} else {
			$event->log("get_instance", $_SERVER['REQUEST_TIME'], 2, "coulduser.class.php", "Could not create instance of event without data", "", "", 0, 0, 0);
			return;
		}

		foreach ($clouduser_array as $index => $clouduser) {
			$this->id = $clouduser["cu_id"];
			$this->cg_id = $clouduser["cu_cg_id"];
			$this->name = $clouduser["cu_name"];
			$this->password = $clouduser["cu_password"];
			$this->forename = $clouduser["cu_forename"];
			$this->lastname = $clouduser["cu_lastname"];
			$this->email = $clouduser["cu_email"];
			$this->street = $clouduser["cu_street"];
			$this->city = $clouduser["cu_city"];
			$this->country = $clouduser["cu_country"];
			$this->phone = $clouduser["cu_phone"];
			$this->status = $clouduser["cu_status"];
			$this->token = $clouduser["cu_token"];
			$this->ccunits = $clouduser["cu_ccunits"];
		}
		return $this;
	}

	// returns an appliance from the db selected by id
	function get_instance_by_id($id) {
		$this->get_instance($id, "");
		return $this;
	}

	// returns an appliance from the db selected by iname
	function get_instance_by_name($name) {
		$this->get_instance("", $name);
		return $this;
	}


	// ---------------------------------------------------------------------------------
	// general clouduser methods
	// ---------------------------------------------------------------------------------




	// checks if given clouduser id is free in the db
	function is_id_free($clouduser_id) {
		global $CLOUD_USER_TABLE;
		global $event;
		$db=openqrm_get_db_connection();
		$rs = &$db->Execute("select cu_id from $CLOUD_USER_TABLE where cu_id=$clouduser_id");
		if (!$rs)
			$event->log("is_id_free", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		if ($rs->EOF) {
			return true;
		} else {
			return false;
		}
	}


	// checks if given clouduser name is free in the db
	function is_name_free($clouduser_name) {
		global $CLOUD_USER_TABLE;
		global $event;
		$db=openqrm_get_db_connection();

		$rs = &$db->Execute("select cu_id from $CLOUD_USER_TABLE where cu_name='$clouduser_name'");
		if (!$rs)
			$event->log("is_name_free", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		if ($rs->EOF) {
			return true;
		} else {
			return false;
		}
	}


	// adds clouduser to the database
	function add($clouduser_fields) {
		global $CLOUD_USER_TABLE;
		global $event;
		if (!is_array($clouduser_fields)) {
			$event->log("add", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", "clouduser_fields not well defined", "", "", 0, 0, 0);
			return 1;
		}
		// set stop time and status to now
		$now=$_SERVER['REQUEST_TIME'];
		$db=openqrm_get_db_connection();
		$result = $db->AutoExecute($CLOUD_USER_TABLE, $clouduser_fields, 'INSERT');
		if (! $result) {
			$event->log("add", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", "Failed adding new clouduser to database", "", "", 0, 0, 0);
		}
	}


	// updates clouduser in the database
	function update($clouduser_id, $clouduser_fields) {
		global $CLOUD_USER_TABLE;
		global $event;
		if (!is_array($clouduser_fields)) {
			$event->log("update", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", "Unable to update clouduser $clouduser_id", "", "", 0, 0, 0);
			return 1;
		}
		$db=openqrm_get_db_connection();
		unset($clouduser_fields["clouduser_id"]);
		$result = $db->AutoExecute($CLOUD_USER_TABLE, $clouduser_fields, 'UPDATE', "cu_id = $clouduser_id");
		if (! $result) {
			$event->log("update", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", "Failed updating clouduser $clouduser_id", "", "", 0, 0, 0);
		}
	}


	// removes clouduser from the database
	function remove($clouduser_id) {
		global $CLOUD_USER_TABLE;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("delete from $CLOUD_USER_TABLE where cu_id=$clouduser_id");
	}

	// removes clouduser from the database by clouduser_name
	function remove_by_name($clouduser_name) {
		global $CLOUD_USER_TABLE;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("delete from $CLOUD_USER_TABLE where cu_name='$clouduser_name'");
	}


	// enables user
	function activate_user_status($cu_id, $stat) {
		global $CLOUD_USER_TABLE;
		global $event;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("update $CLOUD_USER_TABLE set cu_status=$stat where cu_id=$cu_id");
	}


	// set users ccunits
	function set_users_ccunits($cu_id, $ccunits) {
		global $CLOUD_USER_TABLE;
		global $event;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("update $CLOUD_USER_TABLE set cu_ccunits=$ccunits where cu_id=$cu_id");
	}


	// set users password
	function set_users_password($cu_id, $password) {
		global $CLOUD_USER_TABLE;
		global $event;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("update $CLOUD_USER_TABLE set cu_password='$password' where cu_id=$cu_id");
	}


	// returns clouduser name by clouduser_id
	function get_name($clouduser_id) {
		global $CLOUD_USER_TABLE;
		global $event;
		$db=openqrm_get_db_connection();
		$clouduser_set = &$db->Execute("select clouduser_name from $CLOUD_USER_TABLE where cu_id=$clouduser_id");
		if (!$clouduser_set) {
			$event->log("get_name", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		} else {
			if (!$clouduser_set->EOF) {
				return $clouduser_set->fields["cu_name"];
			} else {
				return "idle";
			}
		}
	}


	// returns the number of cloudusers for an clouduser type
	function get_count() {
		global $CLOUD_USER_TABLE;
		$count=0;
		$db=openqrm_get_db_connection();
		$rs = $db->Execute("select count(cu_id) as num from $CLOUD_USER_TABLE");
		if (!$rs) {
			print $db->ErrorMsg();
		} else {
			$count = $rs->fields["num"];
		}
		return $count;
	}



	// returns a list of all clouduser names
	function get_list() {
		global $CLOUD_USER_TABLE;
		$query = "select cu_id, cu_name from $CLOUD_USER_TABLE";
		$clouduser_name_array = array();
		$clouduser_name_array = openqrm_db_get_result_double ($query);
		return $clouduser_name_array;
	}


	// returns a list of all clouduser ids
	function get_all_ids() {
		global $CLOUD_USER_TABLE;
		global $event;
		$clouduser_list = array();
		$query = "select cu_id from $CLOUD_USER_TABLE";
		$db=openqrm_get_db_connection();
		$rs = $db->Execute($query);
		if (!$rs)
			$event->log("get_list", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		else
		while (!$rs->EOF) {
			$clouduser_list[] = $rs->fields;
			$rs->MoveNext();
		}
		return $clouduser_list;

	}


	function checkEmail($email) {
		if (!preg_match("/^[[:alnum:]][a-z0-9_.-]*@[a-z0-9.-]+\.[a-z]{2,4}$/i", $email)) {
			return false;
		} else {
			return true;
		}
	}


	// displays the clouduser-overview
	function display_user($clouduser_name) {
		global $CLOUD_USER_TABLE;
		global $event;
		$db=openqrm_get_db_connection();
		$recordSet = &$db->SelectLimit("select * from $CLOUD_USER_TABLE where cu_name='$clouduser_name'", 1, 0);
		$clouduser_array = array();
		if (!$recordSet) {
			$event->log("display_user", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		} else {
			while (!$recordSet->EOF) {
				array_push($clouduser_array, $recordSet->fields);
				$recordSet->MoveNext();
			}
			$recordSet->Close();
		}
		return $clouduser_array;
	}





	// displays the clouduser-overview
	function display_overview($offset, $limit, $sort, $order) {
		global $CLOUD_USER_TABLE;
		global $event;
		$db=openqrm_get_db_connection();
		$recordSet = &$db->SelectLimit("select * from $CLOUD_USER_TABLE order by $sort $order", $limit, $offset);
		$clouduser_array = array();
		if (!$recordSet) {
			$event->log("display_overview", $_SERVER['REQUEST_TIME'], 2, "clouduser.class.php", $db->ErrorMsg(), "", "", 0, 0, 0);
		} else {
			while (!$recordSet->EOF) {
				array_push($clouduser_array, $recordSet->fields);
				$recordSet->MoveNext();
			}
			$recordSet->Close();
		}
		return $clouduser_array;
	}









// ---------------------------------------------------------------------------------

}

?>
