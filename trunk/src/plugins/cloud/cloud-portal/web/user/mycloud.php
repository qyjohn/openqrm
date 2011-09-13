<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
<title>CloudPro云计算门户</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style type="text/css">



</style>

<link type="text/css" href="js/development-bundle/themes/smoothness/ui.all.css" rel="stylesheet" />
<link type="text/css" href="../css/jquery.css" rel="stylesheet" />
<link type="text/css" rel="stylesheet" href="../css/calendar.css" />
<link type="text/css" rel="stylesheet" href="../css/mycloud.css" />

<script type="text/javascript" language="javascript" src="../js/datetimepicker.js"></script>
<script type="text/javascript" src="js/js/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="js/js/jquery-ui-1.7.1.custom.min.js"></script>
<script language="JavaScript">
	<!--
		if (document.images)
		{
		calimg= new Image(16,16);
		calimg.src="../img/cal.gif";
		}
	//-->
</script>


</head>

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
$DocRoot = $_SERVER["DOCUMENT_ROOT"];
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/deployment.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/include/htmlobject.inc.php";
// special cloud classes
require_once "$RootDir/plugins/cloud/class/clouduser.class.php";
require_once "$RootDir/plugins/cloud/class/clouduserslimits.class.php";
require_once "$RootDir/plugins/cloud/class/cloudrequest.class.php";
require_once "$RootDir/plugins/cloud/class/cloudconfig.class.php";
require_once "$RootDir/plugins/cloud/class/cloudmailer.class.php";
require_once "$RootDir/plugins/cloud/class/cloudimage.class.php";
require_once "$RootDir/plugins/cloud/class/cloudirlc.class.php";
require_once "$RootDir/plugins/cloud/class/cloudiplc.class.php";
require_once "$RootDir/plugins/cloud/class/cloudprivateimage.class.php";
require_once "$RootDir/plugins/cloud/class/cloudselector.class.php";
require_once "$RootDir/plugins/cloud/class/cloudprofile.class.php";

// include the mycloud parts
require_once "./mycloudrequests.php";
require_once "./mycloud_appliances.php";
require_once "./mycloudaccount.php";
require_once "./mycloudimages.php";
require_once "./mycloudprofiles.php";


global $OPENQRM_SERVER_BASE_DIR;
$refresh_delay=5;

$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $CLOUD_REQUEST_TABLE;

// currently we allow up to 11 profiles -> those fits to the vid profile inventory
$max_profile_count=11;
global $max_profile_count;

// who are you ?
$auth_user = $_SERVER['PHP_AUTH_USER'];
global $auth_user;

// gather request parameter in array
foreach ($_REQUEST as $key => $value) {
	if (strncmp($key, "cr_", 3) == 0) {
		$request_fields[$key] = $value;
	}
}
// set ha clone-on deploy
$cr_ha_req = htmlobject_request('cr_ha_req');
if (!strcmp($cr_ha_req, "on")) {
	$request_fields['cr_ha_req']=1;
} else {
	$request_fields['cr_ha_req']=0;
}
$cr_shared_req = htmlobject_request('cr_shared_req');
if (!strcmp($cr_shared_req, "on")) {
	$request_fields['cr_shared_req']=1;
} else {
	$request_fields['cr_shared_req']=0;
}
// default disk size
$cr_disk_req = htmlobject_request('cr_disk_req');
if (!strlen($cr_disk_req)) {
	$request_fields['cr_disk_req']=5000;
}

// to save the cloud request as profile
$profile_name = htmlobject_request('profile_name');
global $profile_name;
global $request_fields;


function date_to_timestamp($date) {
	$day = substr($date, 0, 2);
	$month = substr($date, 3, 2);
	$year = substr($date, 6, 4);
	$hour = substr($date, 11, 2);
	$minute = substr($date, 14, 2);
	$sec = 0;
	$timestamp = mktime($hour, $minute, $sec, $month, $day, $year);
	return $timestamp;
}


function redirect($strMsg, $currenttab = 'tab0', $url = '') {
	global $thisfile;
	if($url == '') {
		$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab='.$currenttab;
	}
	echo "<meta http-equiv=\"refresh\" content=\"0; URL=$url\">";
	exit;
}

// for checking the disk param
function check_is_number($param, $value) {
	if(!ctype_digit($value)){
		$strMsg = "$param 不是一个数字。 <br>";
		redirect($strMsg, "tab0");
		exit(0);
	}
}

// check for allowed chars
function is_allowed_char($text) {
	for ($i = 0; $i<strlen($text); $i++) {
		if (!ctype_alpha($text[$i])) {
			if (!ctype_digit($text[$i])) {
				if (!ctype_space($text[$i])) {
					return false;
				}
			}
		}
	}
	return true;
}


function check_param($param, $value) {
	if (!strlen($value)) {
		$strMsg = "$param 不能为空。 <br>";
		redirect($strMsg, "tab0");
		exit(0);
	}
	if(!ctype_alnum($value)){
		$strMsg = "$param 不能包含特殊字符。 <br>";
		redirect($strMsg, "tab0");
		exit(0);
	}
}


// function to check the user-input for a complete request
function check_request($request_fields, $request_user) {

	check_param("Quantity", $request_fields['cr_resource_quantity']);
	check_param("Kernel Id", $request_fields['cr_kernel_id']);
	check_param("Image Id", $request_fields['cr_image_id']);
	check_param("Memory", $request_fields['cr_ram_req']);
	check_param("CPU", $request_fields['cr_cpu_req']);
	check_param("Network", $request_fields['cr_network_req']);

	// check user limits
	$cloud_user_limit = new clouduserlimits();
	$cloud_user_limit->get_instance_by_cu_id($request_user->id);
	$resource_quantity = $request_fields['cr_resource_quantity'];
	$ram_req = $request_fields['cr_ram_req'];
	$disk_req = $request_fields['cr_disk_req'];
	$cpu_req = $request_fields['cr_cpu_req'];
	$network_req = $request_fields['cr_network_req'];
	if (!$cloud_user_limit->check_limits($resource_quantity, $ram_req, $disk_req, $cpu_req, $network_req)) {
		$strMsg = "User exceeds its Cloud-Limits ! Not adding the request";
		echo "$strMsg <br>";
		flush();
		sleep(4);
		redirect($strMsg, 'tab0', "cloud-manager.php");
		exit(0);
	}

	// parse start date
	$startt = $request_fields['cr_start'];
	$tstart = date_to_timestamp($startt);

	// parse stop date
	$stopp = $request_fields['cr_stop'];
	$tstop = date_to_timestamp($stopp);
	$nowstmp = $_SERVER['REQUEST_TIME'];

	// check that the new stop time is later than the start time
	if ($tstop < ($tstart + 3600)) {
		$strMsg .="Request cannot be created with stop date before start.<br>Request duration must be at least 1 hour.<br>";
		redirect($strMsg, "tab0");
		exit(0);
	}

	// check that the new stop time is later than the now + 1 hour
	if ($tstop < ($nowstmp + 3600)) {
		$strMsg .="Request duration must be at least 1 hour.<br>Not creating the request.<br>";
		redirect($strMsg, "tab0");
		exit(0);
	}

	// check disk param
	check_is_number("Disk", $request_fields['cr_disk_req']);
	if ($request_fields['cr_disk_req'] <= 500) {
		$strMsg .="Disk parameter must be > 500 <br>";
		redirect($strMsg, "tab0");
		exit(0);
	}
	// max disk size
	$cc_disk_conf = new cloudconfig();
	$max_disk_size = $cc_disk_conf->get_value(8);  // 8 is max_disk_size config
	if ($request_fields['cr_disk_req'] > $max_disk_size) {
		$strMsg .="Disk parameter must be <= $max_disk_size <br>";
		redirect($strMsg, "tab0");
		exit(0);
	}
	// max network interfaces
	$max_network_interfaces = $cc_disk_conf->get_value(9);  // 9 is max_network_interfaces
	if ($request_fields['cr_network_req'] > $max_network_interfaces) {
		$strMsg .="Network parameter must be <= $max_network_interfaces <br>";
		redirect($strMsg, "tab0");
		exit(0);
	}
	// max resource per cr
	$max_res_per_cr = $cc_disk_conf->get_value(6);  // 6 is max_resources_per_cr
	if ($request_fields['cr_resource_quantity'] > $max_res_per_cr) {
		$strMsg .="Resource quantity parameter must be <= $max_res_per_cr <br>";
		redirect($strMsg, "tab0");
		exit(0);
	}

	// private image ? if yes do not clone it
	$show_private_image = $cc_disk_conf->get_value(21);	// show_private_image
	if (!strcmp($show_private_image, "true")) {
		$piid = $request_fields['cr_image_id'];
		$private_cu_image = new cloudprivateimage();
		$private_cu_image->get_instance_by_image_id($piid);
		if (strlen($private_cu_image->cu_id)) {
			if ($private_cu_image->cu_id > 0) {
				if ($private_cu_image->cu_id == $request_user->id) {
					// check to make sure we don't start two appliances off of one image!
					$cloudimage_state = new cloudimage();
					$cloudimage_state->get_instance_by_image_id($private_cu_image->image_id);
					if(!$cloudimage_state->id) {
							// set to non-shared !
							$request_fields['cr_shared_req']=0;
					} else {
							$strMsg .="Private Cloud image is already in use! Skipping ...<br>";
							redirect($strMsg, "tab0");
							exit(0);

					}
				} else {
					$strMsg .="Unauthorized request of private Cloud image! Skipping ...<br>";
					redirect($strMsg, "tab0");
					exit(0);
				}
			}
		}
	}

	// ####### start of cloudselector case #######
	// if cloudselector is enabled check if products exist
	$cloud_selector_enabled = $cc_disk_conf->get_value(22);	// cloudselector
	if (!strcmp($cloud_selector_enabled, "true")) {
		$cloudselector = new cloudselector();
		// cpu
		$cs_cpu = htmlobject_request('cr_cpu_req');
		if (!$cloudselector->product_exists_enabled("cpu", $cs_cpu)) {
			$strMsg .="Cloud CPU Product ($cs_cpu) is not existing...<br>";
			redirect($strMsg, "tab0");
			exit(0);
		}
		// disk
		$cs_disk = htmlobject_request('cr_disk_req');
		if (!$cloudselector->product_exists_enabled("disk", $cs_disk)) {
			$strMsg .="Cloud Disk Product ($cs_disk) is not existing...<br>";
			redirect($strMsg, "tab0");
			exit(0);
		}
		// kernel
		$cs_kernel = htmlobject_request('cr_kernel_id');
		if (!$cloudselector->product_exists_enabled("kernel", $cs_kernel)) {
			$strMsg .="Cloud Kernel Product ($cs_kernel) is not existing...<br>";
			redirect($strMsg, "tab0");
			exit(0);
		}
		// memory
		$cs_memory = htmlobject_request('cr_ram_req');
		if (!$cloudselector->product_exists_enabled("memory", $cs_memory)) {
			$strMsg .="Cloud Memory Product ($cs_memory) is not existing...<br>";
			redirect($strMsg, "tab0");
			exit(0);
		}
		// network
		$cs_network = htmlobject_request('cr_network_req');
		if (!$cloudselector->product_exists_enabled("network", $cs_network)) {
			$strMsg .="Cloud Network Product ($cs_network) is not existing...<br>";
			redirect($strMsg, "tab0");
			exit(0);
		}
		// puppet
		if(htmlobject_request('cr_puppet_groups') != '') {
			$puppet_groups_array = htmlobject_request('cr_puppet_groups');
			if (is_array($puppet_groups_array)) {
				foreach($puppet_groups_array as $puppet_group) {
					if (!$cloudselector->product_exists_enabled("puppet", $puppet_group)) {
						$strMsg .="Cloud Puppet Product ($puppet_group) is not existing...<br>";
						redirect($strMsg, "tab0");
						exit(0);
					}
				}
			}
		}
		// quantity
		$cs_quantity = htmlobject_request('cr_resource_quantity');
		if (!$cloudselector->product_exists_enabled("quantity", $cs_quantity)) {
			$strMsg .="Cloud Quantity Product ($cs_quantity) is not existing...<br>";
			redirect($strMsg, "tab0");
			exit(0);
		}
		// resource type
		$cs_resource = htmlobject_request('cr_resource_type_req');
		if (!$cloudselector->product_exists_enabled("resource", $cs_resource)) {
			$strMsg .="Cloud Virtualization Product ($cs_resource) is not existing...<br>";
			redirect($strMsg, "tab0");
			exit(0);
		}


		// ####### end of cloudselector case #######
	}


}




// get admin email
$cc_conf = new cloudconfig();
$cc_admin_email = $cc_conf->get_value(1);  // 1 is admin_email

// check if we got some actions to do
if (htmlobject_request('action') != '') {
	switch (htmlobject_request('action')) {

// ######################## end of cloud-request actions #####################

// here the identifier array is a cloudrequest

		case 'deprovision':
			// get the user
			$clouduser = new clouduser();
			$clouduser->get_instance_by_name($auth_user);
			if (isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $id) {
					$cr_request = new cloudrequest();
					$cr_request->get_instance_by_id($id);
					// is it ours ?
					if ($cr_request->cu_id != $clouduser->id) {
						continue;
					}
					// only allow to deprovision if cr is in state active or no-res
					if (($cr_request->status != 3) && ($cr_request->status != 7)) {
						$strMsg .="Request only can be deprovisioned when in state active <br>";
						continue;
					}
					// mail user before deprovisioning
					$cr_cu_id = $cr_request->cu_id;
					$cl_user = new clouduser();
					$cl_user->get_instance_by_id($cr_cu_id);
					$cu_name = $cl_user->name;
					$cu_email = $cl_user->email;
					$cu_forename = $cl_user->forename;
					$cu_lastname = $cl_user->lastname;
					$cr_start = $cr_request->start;
					$start = date("d-m-Y H-i", $cr_start);
					$cr_stop = $cr_request->stop;
					$stop = date("d-m-Y H-i", $cr_stop);
					$nowstmp = $_SERVER['REQUEST_TIME'];
					$now = date("d-m-Y H-i", $nowstmp);
					// get admin email
					$cc_conf = new cloudconfig();
					$cc_admin_email = $cc_conf->get_value(1);  // 1 is admin_email
					// send mail to user
					$rmail = new cloudmailer();
					$rmail->to = "$cu_email";
					$rmail->from = "$cc_admin_email";
					$rmail->subject = "openQRM Cloud: Your request $id is going to be deprovisioned now !";
					$rmail->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/deprovision_cloud_request.mail.tmpl";
					$arr = array('@@ID@@'=>"$id", '@@FORENAME@@'=>"$cu_forename", '@@LASTNAME@@'=>"$cu_lastname", '@@START@@'=>"$start", '@@STOP@@'=>"$now");
					$rmail->var_array = $arr;
					$rmail->send();
					// send mail to cloud-admin
					$armail = new cloudmailer();
					$armail->to = "$cc_admin_email";
					$armail->from = "$cc_admin_email";
					$armail->subject = "openQRM Cloud: Your request $id is going to be deprovisioned now !";
					$armail->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/deprovision_cloud_request.mail.tmpl";
					$aarr = array('@@ID@@'=>"$id", '@@FORENAME@@'=>"", '@@LASTNAME@@'=>"CloudAdmin", '@@START@@'=>"$start", '@@STOP@@'=>"$now");
					$armail->var_array = $aarr;
					$armail->send();
					// set cr status
					$cr_request->setstatus($id, 'deprovision');

					$strMsg .="Set Cloud request $id to deprovision <br>";
				}
				redirect($strMsg, "tab1");
			}
			break;


		case 'update':
			// get the user
			$clouduser = new clouduser();
			$clouduser->get_instance_by_name($auth_user);
			if (isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $id) {
					$cr_request = new cloudrequest();
					$cr_request->get_instance_by_id($id);
					// is it ours ?
					if ($cr_request->cu_id != $clouduser->id) {
						continue;
					}
					$cr_stop=$_REQUEST['extend_cr_stop'];
					$new_stop_timestmp=date_to_timestamp($cr_stop);
					// only allow to extend requests which are not deprovisioned or done
					if ($cr_request->status == 5) {
						$strMsg .="Request cannot be extended when in state deprovisioned <br>";
						continue;
					}
					if ($cr_request->status == 6) {
						$strMsg .="Request cannot be extended when in state done <br>";
						continue;
					}
					// check that the new stop time is later than the start time
					if ($new_stop_timestmp < ($cr_request->start + 3600)) {
						$strMsg .="Request cannot be extended with stop date before start. Request duration must be at least 1 hour.<br>";
						continue;
					}
					$cr_request->extend_stop_time($id, $new_stop_timestmp);
					$strMsg .="Extended Cloud request $id to $cr_stop <br>";
				}
				redirect($strMsg, "tab1");
			}
			break;

		case 'Create':
			$request_user = new clouduser();
			$request_user->get_instance_by_name("$auth_user");
			// set user id
			$request_fields['cr_cu_id'] = $request_user->id;
			// check if billing is enabled
			$cb_config = new cloudconfig();
			$cloud_billing_enabled = $cb_config->get_value(16);	// 16 is cloud_billing_enabled
			if ($cloud_billing_enabled == 'true') {
				if ($request_user->ccunits < 1) {
					$strMsg .="You do not have any CloudComputing-Units left! Please buy some CC-Units before submitting a request.";
					redirect($strMsg);
					exit(0);
				}
			}

			// check ip-mgmt
			$max_network_interfaces = $cb_config->get_value(9);  // 9 is max_network_interfaces
			$ip_mgmt_config_str = '';
			if(htmlobject_request('cr_ip_mgmt') != '') {
				$ip_mgmt_array = htmlobject_request('cr_ip_mgmt');
				// a select for each nic
				if (is_array($ip_mgmt_array)) {
					for ($mnic = 1; $mnic <= $max_network_interfaces; $mnic++) {
						if (isset($ip_mgmt_array[$mnic])) {
							$ip_mgmt_id = $ip_mgmt_array[$mnic];
							if ($ip_mgmt_id != -1) {
								$ip_mgmt_config_str .= $mnic.":".$ip_mgmt_array[$mnic].",";
							}
						}
					}
				}
			}
			$ip_mgmt_config_str = rtrim($ip_mgmt_config_str, ",");
			$request_fields['cr_ip_mgmt'] = $ip_mgmt_config_str;

			// set the eventual selected puppet groups
			if(htmlobject_request('cr_puppet_groups') != '') {
				$puppet_groups_array = htmlobject_request('cr_puppet_groups');
				if (is_array($puppet_groups_array)) {
					foreach($puppet_groups_array as $puppet_group) {
						$puppet_groups_str .= "$puppet_group,";
					}
					// remove last ,
					$puppet_groups_str = rtrim($puppet_groups_str, ",");
					$request_fields['cr_puppet_groups'] = $puppet_groups_str;
				}
			}

			// check user input
			check_request($request_fields, $request_user);

			// parse start date
			$astartt = $request_fields['cr_start'];
			$astart = date_to_timestamp($astartt);
			$request_fields['cr_start'] = $astart;
			// parse stop date
			$astopp = $request_fields['cr_stop'];
			$astop = date_to_timestamp($astopp);
			$request_fields['cr_stop'] = $astop;
			// id
			$request_fields['cr_id'] = openqrm_db_get_free_id('cr_id', $CLOUD_REQUEST_TABLE);
			$cr_request = new cloudrequest();
			$cr_request->add($request_fields);

			// send mail to admin
			$cr_id = $request_fields['cr_id'];
			$cu_name = $request_user->name;
			$cu_email = $request_user->email;

			$rmail = new cloudmailer();
			$rmail->to = "$cc_admin_email";
			$rmail->from = "$cc_admin_email";
			$rmail->subject = "openQRM Cloud: New request from user $cu_name";
			$rmail->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/new_cloud_request.mail.tmpl";
			$arr = array('@@USER@@'=>"$cu_name", '@@ID@@'=>"$cr_id", '@@OPENQRM_SERVER_IP_ADDRESS@@'=>"$OPENQRM_SERVER_IP_ADDRESS");
			$rmail->var_array = $arr;
			$rmail->send();

			$strMsg="Created new Cloud request";
			redirect($strMsg, "tab1");
			break;



		case 'Save':
			$request_user = new clouduser();
			$request_user->get_instance_by_name("$auth_user");
			// check user input
			check_request($request_fields, $request_user);
			check_param("Profile-name", $profile_name);

			// check if profile name is existing already
			$cloud_profile = new cloudprofile();
			$cloud_profile->get_instance_by_name($profile_name);
			if (strlen($cloud_profile->name)) {
				$strMsg .="Cloud profile name $profile_name already in use. Please choose another name.";
				redirect($strMsg, "tab0");
				exit(0);
			}
			// check max profile number
			$pr_count = $cloud_profile->get_count_per_user($request_user->id);
			if ($pr_count >=$max_profile_count) {
				$strMsg .="Max Cloud profile count reached.<br>Please remove one or more profiles before creating new ones.";
				redirect($strMsg, "tab0");
				exit(0);
			}

			// parse start date
			$astartt = $request_fields['cr_start'];
			$astart = date_to_timestamp($astartt);
			$pr_request_fields['cr_start'] = $astart;
			// parse stop date
			$astopp = $request_fields['cr_stop'];
			$astop = date_to_timestamp($astopp);
			$pr_request_fields['cr_stop'] = $astop;

			// remap fields from cr to pr
			$pr_request_fields['pr_start'] = $request_fields['cr_start'];
			$pr_request_fields['pr_stop'] = $request_fields['cr_stop'];
			$pr_request_fields['pr_kernel_id'] = $request_fields['cr_kernel_id'];
			$pr_request_fields['pr_image_id'] = $request_fields['cr_image_id'];
			$pr_request_fields['pr_ram_req'] = $request_fields['cr_ram_req'];
			$pr_request_fields['pr_cpu_req'] = $request_fields['cr_cpu_req'];
			$pr_request_fields['pr_disk_req'] = $request_fields['cr_disk_req'];
			$pr_request_fields['pr_network_req'] = $request_fields['cr_network_req'];
			$pr_request_fields['pr_resource_quantity'] = $request_fields['cr_resource_quantity'];
			$pr_request_fields['pr_resource_type_req'] = $request_fields['cr_resource_type_req'];
			$pr_request_fields['pr_ha_req'] = $request_fields['cr_ha_req'];
			$pr_request_fields['pr_shared_req'] = $request_fields['cr_shared_req'];
			if (isset($request_fields['cr_puppet_groups'])) {
				$pr_request_fields['pr_puppet_groups'] = $request_fields['cr_puppet_groups'];
			}
			$pr_request_fields['pr_ip_mgmt'] = $request_fields['cr_ip_mgmt'];
			$pr_request_fields['pr_name'] = $profile_name;

			// set user id
			$pr_request_fields['pr_cu_id'] = $request_user->id;
			// id
			$pr_request_fields['pr_id'] = openqrm_db_get_free_id('pr_id', $cloud_profile->_db_table);
			$cloud_profile->add($pr_request_fields);
			$strMsg .="Saving Cloud request as profile $profile_name.";
			redirect($strMsg, "tab2");
			exit(0);
			break;




// ######################## end of cloud-request actions #####################


// ######################## start of cloud-appliance actions #####################

// here the identifier is a cloudappliance !

		case 'restart':
			// get the user
			$clouduser = new clouduser();
			$clouduser->get_instance_by_name($auth_user);
			if (isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $id) {
					$cloud_appliance_restart = new cloudappliance();
					$cloud_appliance_restart->get_instance_by_id($id);
					// is it ours ?
					$cl_request = new cloudrequest();
					$cl_request->get_instance_by_id($cloud_appliance_restart->cr_id);
					if ($cl_request->cu_id != $clouduser->id) {
						continue;
					}
					// check if no other command is currently running
					if ($cloud_appliance_restart->cmd != 0) {
						$strMsg .= "Another command is already registerd for Cloud appliance $id. Please wait until it got executed<br>";
						continue;
					}
					// check that state is active
					if ($cloud_appliance_restart->state == 1) {
						$cloud_appliance_restart->set_cmd($cloud_appliance_restart->id, "restart");
						$strMsg .= "Registered Cloud appliance $id for restart<br>";
					} else {
						$strMsg .= "Can only restart Cloud appliance $id if it is in active state<br>";
						continue;
					}
				}
				redirect($strMsg, 'tab3');
			}
			break;

		case 'pause':
			// get the user
			$clouduser = new clouduser();
			$clouduser->get_instance_by_name($auth_user);
			if (isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $id) {
					$cloud_appliance_pause = new cloudappliance();
					$cloud_appliance_pause->get_instance_by_id($id);
					// is it ours ?
					$cl_request = new cloudrequest();
					$cl_request->get_instance_by_id($cloud_appliance_pause->cr_id);
					if ($cl_request->cu_id != $clouduser->id) {
						continue;
					}
					// check if no other command is currently running
					if ($cloud_appliance_pause->cmd != 0) {
						$strMsg .= "Another command is already registerd for Cloud appliance $id. Please wait until it got executed<br>";
						continue;
					}
					// check that state is active
					if ($cloud_appliance_pause->state == 1) {
						$cloud_appliance_pause->set_cmd($cloud_appliance_pause->id, "stop");
						$cloud_appliance_pause->set_state($cloud_appliance_pause->id, "paused");
						$strMsg .= "Registered Cloud appliance $id to stop (pause)<br>";
						// send mail to cloud-admin
						$armail = new cloudmailer();
						$armail->to = "$cc_admin_email";
						$armail->from = "$cc_admin_email";
						$armail->subject = "openQRM Cloud: Cloud Appliance $id registered for stop (pause)";
						$armail->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/paused_cloud_appliance.mail.tmpl";
						$arr = array('@@USER@@'=>"$clouduser->name", '@@CLOUD_APPLIANCE_ID@@'=>"$id");
						$armail->var_array = $arr;
						$armail->send();
					} else {
						$strMsg .= "Can only pause Cloud appliance $id if it is in active state<br>";
						continue;
					}
				}
				redirect($strMsg, 'tab3');
			}
			break;

		case 'unpause':
			// get the user
			$clouduser = new clouduser();
			$clouduser->get_instance_by_name($auth_user);
			if (isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $id) {
					$cloud_appliance_unpause = new cloudappliance();
					$cloud_appliance_unpause->get_instance_by_id($id);
					// is it ours ?
					$cl_request = new cloudrequest();
					$cl_request->get_instance_by_id($cloud_appliance_unpause->cr_id);
					if ($cl_request->cu_id != $clouduser->id) {
						continue;
					}
					// check if no other command is currently running
					if ($cloud_appliance_unpause->cmd != 0) {
						$strMsg .= "Another command is already registerd for Cloud appliance $id. Please wait until it got executed<br>";
						continue;
					}
					// check if it is in state paused
					if ($cloud_appliance_unpause->state == 0) {
						$cloud_appliance_unpause->set_cmd($cloud_appliance_unpause->id, "start");
						$cloud_appliance_unpause->set_state($cloud_appliance_unpause->id, "active");

						// send mail to cloud-admin
						$armail = new cloudmailer();
						$armail->to = "$cc_admin_email";
						$armail->from = "$cc_admin_email";
						$armail->subject = "openQRM Cloud: Cloud Appliance $id registered for start (unpause)";
						$armail->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/unpaused_cloud_appliance.mail.tmpl";
						$arr = array('@@USER@@'=>"$clouduser->name", '@@CLOUD_APPLIANCE_ID@@'=>"$id");
						$armail->var_array = $arr;
						$armail->send();

						$strMsg .= "Registered Cloud appliance $id to start (unpause)<br>";
					} else {
						$strMsg .= "Can only unpause Cloud appliance $id if it is in paused state<br>";
						continue;
					}
				}
				redirect($strMsg, 'tab3');
			}
			break;

		case 'login':

			if (isset($_REQUEST['identifier'])) {
				// check if to show sshterm-login
				$cc_conf = new cloudconfig();
				$show_sshterm_login = $cc_conf->get_value(17);	// show_sshterm_login
				if (!strcmp($show_sshterm_login, "true")) {
					// is sshterm plugin enabled + started ?
					if (file_exists("$RootDir/plugins/sshterm/.running")) {
						// get the parameters from the plugin config file
						$OPENQRM_PLUGIN_SSHTERM_CONFIG_FILE="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/sshterm/etc/openqrm-plugin-sshterm.conf";
						$store = openqrm_parse_conf($OPENQRM_PLUGIN_SSHTERM_CONFIG_FILE);
						extract($store);
						// get the user
						$clouduser = new clouduser();
						$clouduser->get_instance_by_name($auth_user);
						foreach($_REQUEST['identifier'] as $id) {
							$cloud_appliance_login = new cloudappliance();
							$cloud_appliance_login->get_instance_by_id($id);
							// is it ours ?
							$cl_request = new cloudrequest();
							$cl_request->get_instance_by_id($cloud_appliance_login->cr_id);
							if ($cl_request->cu_id != $clouduser->id) {
								continue;
							}
							// check that state is active
							if ($cloud_appliance_login->state == 1) {
								$sshterm_login_ip_arr = htmlobject_request('sshterm_login_ip');
								$sshterm_login_ip = $sshterm_login_ip_arr["$id"];
								$strMsg .= "Login into Cloud appliance $id ($sshterm_login_ip)<br>";

								$redirect_url="https://$sshterm_login_ip:$OPENQRM_PLUGIN_WEBSHELL_PORT";
								$left=50+($id*50);
								$top=100+($id*50);
				// add the javascript function to open an sshterm
				?>
							<script type="text/javascript">
							function open_sshterm (url) {
								sshterm_window = window.open(url, "<?php echo $sshterm_login_ip; ?>", "width=580,height=420,scrollbars=1,left=<?php echo $left; ?>,top=<?php echo $top; ?>");
								open_sshterm.focus();
							}
							open_sshterm("<?php echo $redirect_url; ?>");
							</script>
				<?php
							} else {
								$strMsg .= "Can only login to Cloud appliance $id if it is in active state<br>";
								continue;
							}
						}
						redirect($strMsg, 'tab3');
					}
				}
			}
			break;



		case 'set-comment':
			// get the user
			$clouduser = new clouduser();
			$clouduser->get_instance_by_name($auth_user);
			if (isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $id) {
					$cloud_appliance_comment = new cloudappliance();
					$cloud_appliance_comment->get_instance_by_id($id);
					// is it ours ?
					$cl_request = new cloudrequest();
					$cl_request->get_instance_by_id($cloud_appliance_comment->cr_id);
					if ($cl_request->cu_id != $clouduser->id) {
						continue;
					}
					$updated_appliance_comment_arr = htmlobject_request('appliance_comment');
					$updated_appliance_comment = $updated_appliance_comment_arr["$id"];
					$updated_appliance_comment_check = trim($updated_appliance_comment);
					// remove any non-violent characters
					$updated_appliance_comment_check = str_replace(" ", "", $updated_appliance_comment_check);
					$updated_appliance_comment_check = str_replace(".", "", $updated_appliance_comment_check);
					$updated_appliance_comment_check = str_replace(",", "", $updated_appliance_comment_check);
					$updated_appliance_comment_check = str_replace("-", "", $updated_appliance_comment_check);
					$updated_appliance_comment_check = str_replace("_", "", $updated_appliance_comment_check);
					$updated_appliance_comment_check = str_replace("(", "", $updated_appliance_comment_check);
					$updated_appliance_comment_check = str_replace(")", "", $updated_appliance_comment_check);
					$updated_appliance_comment_check = str_replace("/", "", $updated_appliance_comment_check);
					if(!is_allowed_char($updated_appliance_comment_check)){
						$strMsg .= "Comment for Cloud appliance $id contains special characters, skipping update <br>";
						continue;
					}
					// here we update the real appliance according the cloudappliance->appliance_id
					$appliance = new appliance();
					$ar_request = array(
						'appliance_comment' => "$updated_appliance_comment",
					);
					$appliance->update($cloud_appliance_comment->appliance_id, $ar_request);
					$strMsg .= "Upated comment for Cloud appliance $id $updated_appliance_comment<br>";
				}
				redirect($strMsg, 'tab3');
			}
			break;



		case 'resize':
			// disk-resize enabled ?
			$cd_config = new cloudconfig();
			$show_disk_resize = $cd_config->get_value(20);	// show_disk_resize
			if (!strcmp($show_disk_resize, "false")) {
				$strMsg = "Disk resize is disabled! Not resizing ... <br>";
				redirect($strMsg, 'tab3');
				exit(0);
			}
			// get the user
			$clouduser = new clouduser();
			$clouduser->get_instance_by_name($auth_user);
			if (isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $id) {
					$cloud_appliance_resize = new cloudappliance();
					$cloud_appliance_resize->get_instance_by_id($id);
					// is it ours ?
					$cl_request = new cloudrequest();
					$cl_request->get_instance_by_id($cloud_appliance_resize->cr_id);
					if ($cl_request->cu_id != $clouduser->id) {
						continue;
					}
					// check user input
					$new_disk_size_arr = htmlobject_request('appliance_disk_resize');
					$new_disk_size = $new_disk_size_arr["$id"];
					$new_disk_size = trim($new_disk_size);
					if (!strlen($new_disk_size)) {
						$strMsg .= "New Disk size for Cloud appliance $id is empty. Not resizing ... <br>";
						continue;
					}
					// check resize
					$appliance = new appliance();
					$appliance->get_instance_by_id($cloud_appliance_resize->appliance_id);
					$image = new image();
					$image->get_instance_by_id($appliance->imageid);
					$cloud_image = new cloudimage();
					$cloud_image->get_instance_by_image_id($image->id);
					$cloud_image_current_disk_size = $cloud_image->disk_size;
					if ($cloud_image_current_disk_size == $new_disk_size) {
						$strMsg .= "New Disk size Cloud appliance $id is equal current Disk size. Not resizing ... <br>";
						continue;
					}
					if ($cloud_image_current_disk_size > $new_disk_size) {
						$strMsg .= "New Disk size Cloud appliance $id needs to be greater current Disk size. Not resizing ... <br>";
						continue;
					}
					// check if no other command is currently running
					if ($cloud_appliance_resize->cmd != 0) {
						$strMsg .= "Another command is already registerd for Cloud appliance $id. Please wait until it got executed<br>";
						continue;
					}
					// check that state is active
					if ($cloud_appliance_resize->state != 1) {
						$strMsg .= "Can only resize Cloud appliance $id if it is in active state<br>";
						continue;
					}
					$additional_disk_space = $new_disk_size - $cloud_image_current_disk_size;
					// put the new size in the cloud_image
					$cloudi_request = array(
						'ci_disk_rsize' => "$new_disk_size",
					);
					$cloud_image->update($cloud_image->id, $cloudi_request);
					// create a new cloud-image resize-life-cycle / using cloudappliance id
					$cloudirlc = new cloudirlc();
					$cirlc_fields['cd_id'] = openqrm_db_get_free_id('cd_id', $cloudirlc->_db_table);
					$cirlc_fields['cd_appliance_id'] = $id;
					$cirlc_fields['cd_state'] = '1';
					$cloudirlc->add($cirlc_fields);
					$strMsg .= "Resizing disk for Cloud appliance $id : $cloud_image_current_disk_size + $additional_disk_space = $new_disk_size<br>";
				}
				redirect($strMsg, 'tab3');
			}
			break;



		case 'private':
			// disk-resize enabled ?
			$cp_config = new cloudconfig();
			$show_private_image = $cp_config->get_value(21);	// show_private_image
			if (!strcmp($show_private_image, "false")) {
				$strMsg = "Private-Image is disabled! Skipping ... <br>";
				redirect($strMsg, 'tab3');
				exit(0);
			}
			// get the user
			$clouduser = new clouduser();
			$clouduser->get_instance_by_name($auth_user);
			if (isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $id) {
					$cloud_appliance_private = new cloudappliance();
					$cloud_appliance_private->get_instance_by_id($id);
					// is it ours ?
					$cl_request = new cloudrequest();
					$cl_request->get_instance_by_id($cloud_appliance_private->cr_id);
					if ($cl_request->cu_id != $clouduser->id) {
						continue;
					}
					// check private
					$appliance = new appliance();
					$appliance->get_instance_by_id($cloud_appliance_private->appliance_id);
					$image = new image();
					$image->get_instance_by_id($appliance->imageid);
					$cloud_image = new cloudimage();
					$cloud_image->get_instance_by_image_id($image->id);
					$cloud_image_current_disk_size = $cloud_image->disk_size;
					// check if no other command is currently running
					if ($cloud_appliance_private->cmd != 0) {
						$strMsg .= "Another command is already registerd for Cloud appliance $id. Please wait until it got executed<br>";
						continue;
					}
					// check that state is active
					if ($cloud_appliance_private->state != 1) {
						$strMsg .= "Can only create a private image from Cloud appliance $id if it is in active state<br>";
						continue;
					}
					// put the size + clone name in the cloud_image
					$time_token = $_SERVER['REQUEST_TIME'];
					$private_image_name = str_replace("cloud", "private", $image->name);
					$private_image_name = substr($private_image_name,0,11).$time_token;
					$cloudi_request = array(
						'ci_disk_rsize' => $cloud_image_current_disk_size,
						'ci_clone_name' => $private_image_name,
					);
					$cloud_image->update($cloud_image->id, $cloudi_request);
					// create a new cloud-image private-life-cycle / using the cloudappliance id
					$cloudiplc = new cloudiplc();
					$ciplc_fields['cp_id'] = openqrm_db_get_free_id('cp_id', $cloudiplc->_db_table);
					$ciplc_fields['cp_appliance_id'] = $id;
					$ciplc_fields['cp_cu_id'] = $clouduser->id;
					$ciplc_fields['cp_state'] = '1';
					$ciplc_fields['cp_start_private'] = $_SERVER['REQUEST_TIME'];
					$cloudiplc->add($ciplc_fields);
					$strMsg .= "Creating a private image $private_image_name from Cloud appliance $id<br>";
				}
				redirect($strMsg, 'tab3');
			}
			break;


// ######################## end of cloud-appliance actions #####################



	}
}







// ################### main output section ###############
$output = array();
// is the cloud enabled ?
$cc_config = new cloudconfig();
$cloud_enabled = $cc_config->get_value(15);	// 15 is cloud_enabled

if ($cloud_enabled != 'true') {	
	$strMsg = "CloudPro云计算门户正在维护中。<br>请在维护结束后再来访问。";
	redirect($strMsg, "tab0", "/cloud-portal?strMsg=$strMsg");
	exit(0);
}


// include header
include "$DocRoot/cloud-portal/mycloud-head.php";


$cloudu = new clouduser();
$cloudu->get_instance_by_name($auth_user);
if ($cloudu->status == 1) {
	$output[] = array('label' => "<a href=\"$thisfile?currenttab=tab0\">申请资源</a>", 'value' => my_cloud_create_request());
	// check if in extent cr
	$cr_in_extend = 0;
	if ((htmlobject_request('action') != '') && (isset($_REQUEST['identifier']))) {
		switch (htmlobject_request('action')) {
			case 'extend':
				foreach($_REQUEST['identifier'] as $id) {
					$output[] = array('label' => '申请延期', 'value' => my_cloud_extend_request($id));
					$cr_in_extend = 1;
				}
		}
	}
	if ($cr_in_extend == 0) {
		$output[] = array('label' => "<a href=\"$thisfile?currenttab=tab1\">所有申请</a>", 'value' => my_cloud_manager());
	}
	$output[] = array('label' => "<a href=\"$thisfile?currenttab=tab2\">使用状况</a>", 'value' => mycloud_profiles());
	$output[] = array('label' => "<a href=\"$thisfile?currenttab=tab3\">云器件</a>", 'value' => my_cloud_appliances());
	$output[] = array('label' => "<a href=\"$thisfile?currenttab=tab4\">系统映像</a>", 'value' => mycloud_images());
	$output[] = array('label' => "<a href=\"#\" onClick=\"javascript:window.open('vid/','','location=0,status=0,scrollbars=1,width=920,height=820,left=300,top=50,screenX=300,screenY=50');\">架构设计</a>", 'value' => "");
	$output[] = array('label' => "<a href=\"$thisfile?currenttab=tab6\">我的帐号</a>", 'value' => mycloud_account());
	$output[] = array('label' => "<a href=\"$thisfile?currenttab=tab7\">帮助文档</a>", 'value' => mycloud_documentation());
	$output[] = array('label' => "<a href=\"/cloud-portal/mycloud-logout.php\">登离系统</a>", 'value' => "");
} else {
	$output[] = array('label' => 'Your account has been disabled', 'value' => my_cloud_account_disabled());
}

echo htmlobject_tabmenu($output);

// include footer
include "$DocRoot/cloud-portal/mycloud-bottom.php";

?>


