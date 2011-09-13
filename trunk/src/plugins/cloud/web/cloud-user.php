<SCRIPT LANGUAGE="JavaScript">
<!-- Original:  ataxx@visto.com -->

function getRandomNum(lbound, ubound) {
	return (Math.floor(Math.random() * (ubound - lbound)) + lbound);
}

function getRandomChar(number, lower, upper, other, extra) {
	var numberChars = "0123456789";
	var lowerChars = "abcdefghijklmnopqrstuvwxyz";
	var upperChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	var otherChars = "`~!@#$%^&*()-_=+[{]}\\|;:'\",<.>/? ";
	var charSet = extra;
	if (number == true)
		charSet += numberChars;
	if (lower == true)
		charSet += lowerChars;
	if (upper == true)
		charSet += upperChars;
	if (other == true)
		charSet += otherChars;
	return charSet.charAt(getRandomNum(0, charSet.length));
}
function getPassword(length, extraChars, firstNumber, firstLower, firstUpper, firstOther, latterNumber, latterLower, latterUpper, latterOther) {
	var rc = "";
	if (length > 0)
		rc = rc + getRandomChar(firstNumber, firstLower, firstUpper, firstOther, extraChars);
	for (var idx = 1; idx < length; ++idx) {
		rc = rc + getRandomChar(latterNumber, latterLower, latterUpper, latterOther, extraChars);
	}
	return rc;
}

function statusMsg(msg) {
	window.status=msg;
	return true;
}


</script>

<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />

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


// error_reporting(E_ALL);
$thisfile = basename($_SERVER['PHP_SELF']);
$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
$BaseDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/';
$CloudDir = $_SERVER["DOCUMENT_ROOT"].'/cloud-portal/';
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/deployment.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/include/htmlobject.inc.php";
// special clouduser class
require_once "$RootDir/plugins/cloud/class/clouduser.class.php";
require_once "$RootDir/plugins/cloud/class/cloudusergroup.class.php";
require_once "$RootDir/plugins/cloud/class/clouduserslimits.class.php";
require_once "$RootDir/plugins/cloud/class/cloudconfig.class.php";

global $OPENQRM_SERVER_BASE_DIR;
$refresh_delay=5;

$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $OPENQRM_WEB_PROTOCOL;

// if ldap is enabled do not allow access the the openQRM cloud user administration
$central_user_management = false;
if (file_exists("$RootDir/plugins/ldap/.running")) {
	$central_user_management = true;
}

// check if we got some actions to do
if(htmlobject_request('action') != '') {
	switch (htmlobject_request('action')) {
		case 'delete':
			if(isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $id) {
					$cl_user = new clouduser();
					$cl_user->get_instance_by_id($id);
					// remove user from htpasswd
					$username = $cl_user->name;
					$openqrm_server_command="htpasswd -D $CloudDir/user/.htpasswd $username";
					$output = shell_exec($openqrm_server_command);
					// remove permissions and limits
					$cloud_user_limit = new clouduserlimits();
					$cloud_user_limit->remove_by_cu_id($id);
					// remove from db
					$cl_user->remove($id);
				}
			}
			break;

		case 'enable':
			if(isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $id) {
					$cl_user = new clouduser();
					$cl_user->get_instance_by_id($id);
					$cl_user->activate_user_status($id, 1);
				}
			}
			break;

		case 'disable':
			if(isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $id) {
					$cl_user = new clouduser();
					$cl_user->get_instance_by_id($id);
					$cl_user->activate_user_status($id, 0);
				}
			}
			break;

		case 'update':
			if(isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $id) {
					$up_ccunits = $_REQUEST['cu_ccunits'];
					$cl_user = new clouduser();
					$cl_user->get_instance_by_id($id);
					$cl_user->set_users_ccunits($id, $up_ccunits[$id]);
				}
			}
			break;

		case 'limit':
			// gather user_limits parameter in array
			foreach ($_REQUEST as $key => $value) {
				if (strncmp($key, "cl_", 3) == 0) {
					$user_limits_fields[$key] = $value;
				}
			}
			$cloud_user_id = $_REQUEST['cl_cu_id'];
			$cloud_user_limit = new clouduserlimits();
			$cloud_user_limit->get_instance_by_cu_id($cloud_user_id);
			$cl_id = $cloud_user_limit->id;
			$cloud_user_limit->update($cl_id, $user_limits_fields);
			// echo "Updated limits for Cloud user $cloud_user_id<br>";
			break;

	}
}




function cloud_user_manager() {

	global $OPENQRM_USER;
	global $OPENQRM_SERVER_IP_ADDRESS;
	global $OPENQRM_WEB_PROTOCOL;
	global $thisfile;
	global $central_user_management;

	$table = new htmlobject_db_table('cu_id', 'DESC');
	$cc_conf = new cloudconfig();
	// get external name
	$external_portal_name = $cc_conf->get_value(3);  // 3 is the external name
	if (!strlen($external_portal_name)) {
		$external_portal_name = "$OPENQRM_WEB_PROTOCOL://$OPENQRM_SERVER_IP_ADDRESS/cloud-portal";
	}
	$arHead = array();

	$arHead['cu_id'] = array();
	$arHead['cu_id']['title'] ='ID';

	$arHead['cu_name'] = array();
	$arHead['cu_name']['title'] ='Name';

	$arHead['cu_forename'] = array();
	$arHead['cu_forename']['title'] ='Fore name';

	$arHead['cu_lastname'] = array();
	$arHead['cu_lastname']['title'] ='Last name';

	$arHead['cu_cg_id'] = array();
	$arHead['cu_cg_id']['title'] ='Group';

	$arHead['cu_email'] = array();
	$arHead['cu_email']['title'] ='Email';

	$arHead['cu_ccunits'] = array();
	$arHead['cu_ccunits']['title'] ='CC-Units';

	$arHead['cu_status'] = array();
	$arHead['cu_status']['title'] ='Status';

	$arBody = array();

	// db select
	$cl_user_count = 0;
	$cl_user = new clouduser();
	$user_array = $cl_user->display_overview($table->offset, $table->limit, $table->sort, $table->order);
	foreach ($user_array as $index => $cu) {
		$cu_status = $cu["cu_status"];
		if ($cu_status == 1) {
			$status_icon = "<img src=\"/cloud-portal/img/active.png\">";
		} else {
			$status_icon = "<img src=\"/cloud-portal/img/inactive.png\">";
		}
		// set the ccunits input
		$ccunits = $cu["cu_ccunits"];
		if (!strlen($ccunits)) {
			$ccunits = 0;
		}
		$cu_id = $cu["cu_id"];
		$ccunits_input = "<input type=\"text\" name=\"cu_ccunits[$cu_id]\" value=\"$ccunits\" size=\"5\ maxsize=\"10\">";

		// user login link
		$tclu = new clouduser();
		$tclu->get_instance_by_id($cu_id);
		$user_auth_str = "://".$tclu->name.":".$tclu->password."@";
		$external_portal_user_auth = str_replace("://", $user_auth_str, $external_portal_name);
		$user_login_link = "<a href=\"".$external_portal_user_auth."/user/mycloud.php\" title=\"Login\" target=\"_BLANK\" onmouseover=\"return statusMsg('')\">".$tclu->name."</a>";
		// group
		$cloudusergroup = new cloudusergroup();
		$cloudusergroup->get_instance_by_id($cu["cu_cg_id"]);
		$cg_name = $cloudusergroup->name;

		$arBody[] = array(
			'cu_id' => $cu["cu_id"],
			'cu_name' => $user_login_link,
			'cu_forename' => $cu["cu_forename"],
			'cu_lastname' => $cu["cu_lastname"],
			'cu_cg_id' => $cg_name,
			'cu_email' => $cu["cu_email"],
			'cu_ccunits' => $ccunits_input,
			'cu_status' => $status_icon,
		);
		$cl_user_count++;
	}

	$table->id = 'Tabelle';
	$table->css = 'htmlobject_table';
	$table->border = 1;
	$table->cellspacing = 0;
	$table->cellpadding = 3;
	$table->form_action = $thisfile;
	$table->identifier_type = "checkbox";
	$table->head = $arHead;
	$table->body = $arBody;
	if ($OPENQRM_USER->role == "administrator") {
		if (!$central_user_management) {
			$table->bottom = array('update', 'enable', 'disable', 'limits', 'delete');
		} else {
			$table->bottom = array('update', 'enable', 'disable', 'limits');
		}
		$table->identifier = 'cu_id';
	}
	$table->max = $cl_user->get_count();

	if (!$central_user_management) {
		$create_user_link = '<a href='.$thisfile.'?action=create>Create new Cloud User</a>';
	} else {
		$create_user_link = '';
	}

	//------------------------------------------------------------ set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './tpl/' . 'cloud-user-manager-tpl.php');
	$t->setVar(array(
		'thisfile' => $thisfile,
		'create_user_link' => $create_user_link,
		'external_portal_name' => $external_portal_name,
		'cloud_user_table' => $table->get_string(),
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;
}



function cloud_create_user() {

	global $OPENQRM_USER;
	global $OPENQRM_SERVER_IP_ADDRESS;
	global $OPENQRM_WEB_PROTOCOL;
	global $thisfile;
	$cc_conf = new cloudconfig();
	// get external name
	$external_portal_name = $cc_conf->get_value(3);  // 3 is the external name
	if (!strlen($external_portal_name)) {
		$external_portal_name = "$OPENQRM_WEB_PROTOCOL://$OPENQRM_SERVER_IP_ADDRESS/cloud-portal";
	}
	$cu_name = htmlobject_input('cu_name', array("value" => '', "label" => 'User name'), 'text', 20);
	// root password input plus generate password button
	$generate_pass = "Password&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input name=\"cu_password\" type=\"password\" id=\"cu_password\" value=\"\" size=\"10\" maxlength=\"10\">";
	$generate_pass .= "<input type=\"button\" name=\"gen\" value=\"generate\" onclick=\"this.form.cu_password.value=getPassword(10, false, true, true, true, false, true, true, true, false);\"><br>";
	// the user group select
	$cloudusergroup = new cloudusergroup();
	$cloudusergroup_list = array();
	$cloudusergroup_list_select = array();
	$cloudusergroup_list = $cloudusergroup->get_list();
	foreach ($cloudusergroup_list as $id => $cg) {
		$cloudusergroup_list_select[] = array("value" => $cg['value'], "label" => $cg['label']);
	}
	$cu_forename = htmlobject_input('cu_forename', array("value" => '', "label" => 'Fore name'), 'text', 50);
	$cu_lastname = htmlobject_input('cu_lastname', array("value" => '', "label" => 'Last name'), 'text', 50);
	$cu_email = htmlobject_input('cu_email', array("value" => '', "label" => 'Email'), 'text', 50);
	$cu_street = htmlobject_input('cu_street', array("value" => '', "label" => 'Street+number'), 'text', 100);
	$cu_city = htmlobject_input('cu_city', array("value" => '', "label" => 'City'), 'text', 100);
	$cu_country = htmlobject_input('cu_country', array("value" => '', "label" => 'Country'), 'text', 100);
	$cu_phone = htmlobject_input('cu_phone', array("value" => '', "label" => 'Phone'), 'text', 100);
	//------------------------------------------------------------ set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './tpl/' . 'cloud-user-create-tpl.php');
	$t->setVar(array(
		'cu_name' => $cu_name,
		'generate_pass' => $generate_pass,
		'cu_cg' => htmlobject_select('cu_cg_id', $cloudusergroup_list_select, 'Group'),
		'cu_forename' => $cu_forename,
		'cu_lastname' => $cu_lastname,
		'cu_email' => $cu_email,
		'cu_street' => $cu_street,
		'cu_city' => $cu_city,
		'cu_country' => $cu_country,
		'cu_phone' => $cu_phone,
		'thisfile' => 'cloud-action.php',
		'external_portal_name' => $external_portal_name,
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;
}




function cloud_set_user_limits($cloud_user_id) {

	global $OPENQRM_USER;
	global $thisfile;

	$cloud_user = new clouduser();
	$cloud_user->get_instance_by_id($cloud_user_id);

	$cloud_user_limit = new clouduserlimits();
	$cloud_user_limit->get_instance_by_cu_id($cloud_user_id);
	$resource_limit = $cloud_user_limit->resource_limit;
	$memory_limit = $cloud_user_limit->memory_limit;
	$disk_limit = $cloud_user_limit->disk_limit;
	$cpu_limit = $cloud_user_limit->cpu_limit;
	$network_limit = $cloud_user_limit->network_limit;

	$cl_resource_limit = htmlobject_input('cl_resource_limit', array("value" => $resource_limit, "label" => 'Max Resource'), 'text', 20);
	$cl_memory_limit = htmlobject_input('cl_memory_limit', array("value" => $memory_limit, "label" => 'Max Memory'), 'text', 20);
	$cl_disk_limit = htmlobject_input('cl_disk_limit', array("value" => $disk_limit, "label" => 'Max Disk Space'), 'text', 20);
	$cl_cpu_limit = htmlobject_input('cl_cpu_limit', array("value" => $cpu_limit, "label" => 'Max CPU'), 'text', 20);
	$cl_network_limit = htmlobject_input('cl_network_limit', array("value" => $network_limit, "label" => 'Max NIC'), 'text', 20);

	//------------------------------------------------------------ set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './tpl/' . 'cloud-user-set-limit-tpl.php');
	$t->setVar(array(
		'cloud_user_id' => $cloud_user_id,
		'cu_name' => $cloud_user->name,
		'cl_resource_limit' => $cl_resource_limit,
		'cl_memory_limit' => $cl_memory_limit,
		'cl_disk_limit' => $cl_disk_limit,
		'cl_cpu_limit' => $cl_cpu_limit,
		'cl_network_limit' => $cl_network_limit,
		'thisfile' => $thisfile,
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;
}







$output = array();

if(htmlobject_request('action') != '') {
	switch (htmlobject_request('action')) {
		case 'create':
			if (!$central_user_management) {
				$output[] = array('label' => 'Create Cloud User', 'value' => cloud_create_user());
			}
			break;
		case 'limits':
			if(isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $id) {
					$output[] = array('label' => 'Cloud User Limits', 'value' => cloud_set_user_limits($id));
				}
			}
			$output[] = array('label' => 'Cloud User Manager', 'value' => cloud_user_manager());
			break;
		default:
			$output[] = array('label' => 'Cloud User Manager', 'value' => cloud_user_manager());
			break;
	}
} else {
	$output[] = array('label' => 'Cloud User Manager', 'value' => cloud_user_manager());
}




echo htmlobject_tabmenu($output);
?>
