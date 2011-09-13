<html>
<head>
<style type="text/css">
	<!--
	-->
	</style>
	<script type="text/javascript" language="javascript" src="../../js/datetimepicker.js"></script>
	<script language="JavaScript">
	<!--
		if (document.images)
		{
		calimg= new Image(16,16);
		calimg.src="../../img/cal.gif";
		}
	//-->
</script>
<link type="text/css" rel="stylesheet" href="../../css/calendar.css">
<link rel="stylesheet" type="text/css" href="../../../../css/htmlobject.css" />
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


$thisfile = basename($_SERVER['PHP_SELF']);
$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/openqrm_server.class.php";
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();

// define Cloud user and password to authenticate the soap-client against the openQRM Cloud
$cloud_user = "matt";
$cloud_password = "mattmatt";

// url for the wdsl
$surl = "http://$OPENQRM_SERVER_IP_ADDRESS/openqrm/boot-service/clouduser.wdsl";

// turn off the WSDL cache
ini_set("soap.wsdl_cache_enabled", "0");

// create the soap-client
$client = new SoapClient($surl, array('soap_version' => SOAP_1_2, 'trace' => 1, 'login'=> $cloud_user, 'password' => $cloud_password ));

// var_dump($client->__getFunctions());

// ######################### actions start #####################################

if (isset($_REQUEST['action'])) {
	$action = $_REQUEST['action'];
} else {
	$action = '';
}
// gather user parameter in array
foreach ($_REQUEST as $key => $value) {
	if (strncmp($key, "cr_", 3) == 0) {
		$request_fields[$key] = $value;
	}
}
switch ($action) {

	// ######################### cloud Provisioning example ####################
	case 'provision':
		try {
			$provision_parameters = "user,".$cloud_user.",".$cloud_password.",".$request_fields['cr_username'].",".$request_fields['cr_start'].",".$request_fields['cr_stop'].",".$request_fields['cr_kernel'].",".$request_fields['cr_image'].",".$request_fields['cr_ram_req'].",".$request_fields['cr_cpu_req'].",".$request_fields['cr_disk_req'].",".$request_fields['cr_network_req'].",".$request_fields['cr_resource_quantity'].",".$request_fields['cr_virtualization'].",".$request_fields['cr_ha_req'].",".$request_fields['cr_puppet'];
			echo "provision params : $provision_parameters <br>";
			$res = $client->CloudProvision($provision_parameters);
		} catch (Exception $e) {
			$res = $e->getMessage();
		}
		echo "provision : $res <br>";
		break;

	// ######################### cloud De-Provisioning example #################
	case 'deprovision':
		$deprovision_parameters = "user,".$cloud_user.",".$cloud_password.",".$request_fields['cr_id'];
		$cr_id = $request_fields['cr_id'];
		echo "deprovision params : $deprovision_parameters <br>";
		try {
			$res = $client->CloudDeProvision($deprovision_parameters);
		} catch (Exception $e) {
			$res = $e->getMessage();
		}
		echo "deprovision request $cr_id : $res <br>";
		break;

	// ######################### cloud sshterm example #########################
	case 'appliance_login':
		$cloudappliance_ip = $request_fields['cr_cloudappliance_ip'];
		sshterm_login($cloudappliance_ip);
		break;
	// ######################### cloud appliance command example ####################
	case 'appliance_pause':
		$appliance_pause_parameters = "user,".$cloud_user.",".$cloud_password.",".$request_fields['cr_cloudappliance_id'].",stop";
		try {
			$res = $client->CloudApplianceCommand($appliance_pause_parameters);
		} catch (Exception $e) {
			$res = $e->getMessage();
		}
		$cloudappliance_id = $request_fields['cr_cloudappliance_id'];
		echo "Registered Cloud appliance command  pause/stop on appliance id $cloudappliance_id : $res<br>";
		break;

	case 'appliance_unpause':
		$appliance_unpause_parameters = "user,".$cloud_user.",".$cloud_password.",".$request_fields['cr_cloudappliance_id'].",start";
		try {
			$res = $client->CloudApplianceCommand($appliance_unpause_parameters);
		} catch (Exception $e) {
			$res = $e->getMessage();
		}
		$cloudappliance_id = $request_fields['cr_cloudappliance_id'];
		echo "Registered Cloud appliance command  unpause/start on appliance id $cloudappliance_id : $res<br>";
		break;

	case 'appliance_restart':
		$appliance_restart_parameters = "user,".$cloud_user.",".$cloud_password.",".$request_fields['cr_cloudappliance_id'].",restart";
		try {
			$res = $client->CloudApplianceCommand($appliance_restart_parameters);
		} catch (Exception $e) {
			$res = $e->getMessage();
		}
		$cloudappliance_id = $request_fields['cr_cloudappliance_id'];
		echo "Registered Cloud appliance command  restart on appliance id $cloudappliance_id : $res<br>";
		break;

	case 'appliance_comment':
		$cloudappliance_id = $request_fields['cr_cloudappliance_id'];
		$updated_appliance_comment_array = $request_fields['cr_appliance_comment'];
		$updated_appliance_comment = $updated_appliance_comment_array[$cloudappliance_id];
		$appliance_comment_parameters = "user,".$cloud_user.",".$cloud_password.",".$request_fields['cr_cloudappliance_id'].",".$updated_appliance_comment;
		try {
			$res = $client->CloudApplianceComment($appliance_comment_parameters);
		} catch (Exception $e) {
			$res = $e->getMessage();
		}
		echo "Updated comment of Cloud appliance id $cloudappliance_id : $res<br>";
		break;

	case 'update_user':
		$cloud_user_upate_parameters = "user,".$cloud_user.",".$cloud_password.",".$cloud_user.",".$request_fields['cr_user_lastname'].",".$request_fields['cr_user_forename'].",".$request_fields['cr_user_email'].",".$request_fields['cr_user_street'].",".$request_fields['cr_user_city'].",".$request_fields['cr_user_country'].",".$request_fields['cr_user_phone'];
		try {
			$res = $client->CloudUserSetDetails($cloud_user_upate_parameters);
		} catch (Exception $e) {
			$res = $e->getMessage();
		}
		echo "Updated Cloud User Details of $cloud_user : $res<br>";
		break;

	case 'update_password':
		$cloud_user_upate_pass_parameters = "user,".$cloud_user.",".$cloud_password.",".$cloud_user.",".$request_fields['cr_user_password'];
		try {
			$res = $client->CloudUserSetPassword($cloud_user_upate_pass_parameters);
		} catch (Exception $e) {
			$res = $e->getMessage();
		}
		echo "Updated Password of Cloud User $cloud_user : $res<br>";
		break;


}



// ######################### actions end #######################################

echo "<br>";
echo "<h2>Examples for the openQRM User SOAP-Service</h2>";

// ######################### form provision start ##############################

echo "<hr>";
echo "<h4>Provisioning</h4>";
echo "<form action=$thisfile method=post>";
echo "<p>";
echo "<table border=1><tr><td>";

// ######################### Cloud method example ##############################

// set the cloud user name
echo "<input type=hidden name='cr_username' value=\"$cloud_user\">";

// ######################### set start time ####################################

$now = date("d-m-Y H:i", $_SERVER['REQUEST_TIME']);
echo "Start time</td><td><input id=\"cr_start\" name=\"cr_start\" type=\"text\" size=\"25\" value=\"$now\">";
echo "<a href=\"javascript:NewCal('cr_start','ddmmyyyy',true,24,'dropdown',true)\">";
echo "<img src=\"../../img/cal.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Pick a date\">";
echo "</a></td></tr><tr><td>";

// ######################### set stop time #####################################

$tomorrow = date("d-m-Y H:i", $_SERVER['REQUEST_TIME'] + 86400);
echo "Stop time</td><td><input id=\"cr_stop\" name=\"cr_stop\" type=\"text\" size=\"25\" value=\"$tomorrow\">";
echo "<a href=\"javascript:NewCal('cr_stop','ddmmyyyy',true,24,'dropdown',true)\">";
echo "<img src=\"../../img/cal.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Pick a date\">";
echo "</a></td></tr><tr><td>";


// ######################### kernel method examples ############################

// a select-box including all kernels
try {
	$kernelgetlist_parameter = "user,$cloud_user,$cloud_password";
	$kernel_list = $client->KernelGetList($kernelgetlist_parameter);
	echo 'Kernel</td><td><select name="cr_kernel" size="1">';
	foreach($kernel_list as $kernel) {
		echo "<option value=\"$kernel\">$kernel</option>";
	}
	echo '</select></td></tr><tr><td>';
} catch (Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "<br>";
}


// ######################### image method examples #############################

// a select-box including all images
try {
	$imagegetlist_parameter = "user,$cloud_user,$cloud_password";
	$image_list = $client->ImageGetList($imagegetlist_parameter);
	echo 'Image</td><td><select name="cr_image" size="1">';
	foreach($image_list as $image) {
		echo "<option value=\"$image\">$image</option>";
	}
	echo '</select></td></tr><tr><td>';
} catch (Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "<br>";
}

// ######################### virtualization method examples ####################

// a select-box including all virtualization types
try {
	$virtualizationgetlist_parameter = "user,$cloud_user,$cloud_password";
	$virtualization_list = $client->VirtualizationGetList($virtualizationgetlist_parameter);
	echo 'Type</td><td><select name="cr_virtualization" size="1">';
	foreach($virtualization_list as $virtualization) {
		echo "<option value=\"$virtualization\">$virtualization</option>";
	}
	echo '</select></td></tr><tr><td>';
} catch (Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "<br>";
}

// ######################### puppet method examples ############################

// a select-box including all available puppet groups
try {
	$puppetgetlist_parameter = "user,$cloud_user,$cloud_password";
	$puppet_list = $client->PuppetGetList($puppetgetlist_parameter);
	echo 'Puppet</td><td><select name="cr_puppet" size="1">';
	echo "<option value=\"\">none</option>";
	foreach($puppet_list as $puppet) {
		echo "<option value=\"$puppet\">$puppet</option>";
	}
	echo '</select></td></tr><tr><td>';
} catch (Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "<br>";
}

// ######################### Cloud products (cloudselector) ####################

// select how many systems to deploy
try {
	$quantity_product_list_parameter = "user,$cloud_user,$cloud_password,quantity";
	$quantity_product_list = $client->ProductGetList($quantity_product_list_parameter);
	echo 'Quantity</td><td><select name="cr_resource_quantity" size="1">';
	foreach($quantity_product_list as $product_id) {
		$quantity_product_details_parameter = "user,$cloud_user,$cloud_password,$product_id";
		$quantity_product_details = $client->ProductGetDetails($quantity_product_details_parameter);
		$product_quantity_parameter = $quantity_product_details['quantity'];
		if (strlen($product_quantity_parameter)) {
			echo "<option value=\"$product_quantity_parameter\">$product_quantity_parameter</option>";
		}
	}
	echo '</select></td></tr><tr><td>';
} catch (Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "<br>";
}

// select how much memory
try {
	$memory_product_list_parameter = "user,$cloud_user,$cloud_password,memory";
	$memory_product_list = $client->ProductGetList($memory_product_list_parameter);
	echo 'Memory</td><td><select name="cr_ram_req" size="1">';
	foreach($memory_product_list as $product_id) {
		$memory_product_details_parameter = "user,$cloud_user,$cloud_password,$product_id";
		$memory_product_details = $client->ProductGetDetails($memory_product_details_parameter);
		$product_memory_parameter = $memory_product_details['quantity'];
		if (strlen($product_memory_parameter)) {
			echo "<option value=\"$product_memory_parameter\">$product_memory_parameter</option>";
		}
	}
	echo '</select></td></tr><tr><td>';
} catch (Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "<br>";
}


// select how many cpus
try {
	$cpu_product_list_parameter = "user,$cloud_user,$cloud_password,cpu";
	$cpu_product_list = $client->ProductGetList($cpu_product_list_parameter);
	echo 'CPU</td><td><select name="cr_cpu_req" size="1">';
	foreach($cpu_product_list as $product_id) {
		$cpu_product_details_parameter = "user,$cloud_user,$cloud_password,$product_id";
		$cpu_product_details = $client->ProductGetDetails($cpu_product_details_parameter);
		$product_cpu_parameter = $cpu_product_details['quantity'];
		if (strlen($product_cpu_parameter)) {
			echo "<option value=\"$product_cpu_parameter\">$product_cpu_parameter</option>";
		}
	}
	echo '</select></td></tr><tr><td>';
} catch (Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "<br>";
}

// select disk-size
try {
	$disk_product_list_parameter = "user,$cloud_user,$cloud_password,disk";
	$disk_product_list = $client->ProductGetList($disk_product_list_parameter);
	echo 'Disk</td><td><select name="cr_disk_req" size="1">';
	foreach($disk_product_list as $product_id) {
		$disk_product_details_parameter = "user,$cloud_user,$cloud_password,$product_id";
		$disk_product_details = $client->ProductGetDetails($disk_product_details_parameter);
		$product_disk_parameter = $disk_product_details['quantity'];
		if (strlen($product_disk_parameter)) {
			echo "<option value=\"$product_disk_parameter\">$product_disk_parameter</option>";
		}
	}
	echo '</select></td></tr><tr><td>';
} catch (Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "<br>";
}


// select how many network interfaces
try {
	$network_product_list_parameter = "user,$cloud_user,$cloud_password,network";
	$network_product_list = $client->ProductGetList($network_product_list_parameter);
	echo 'NIC</td><td><select name="cr_network_req" size="1">';
	foreach($network_product_list as $product_id) {
		$network_product_details_parameter = "user,$cloud_user,$cloud_password,$product_id";
		$network_product_details = $client->ProductGetDetails($network_product_details_parameter);
		$product_network_parameter = $network_product_details['quantity'];
		if (strlen($product_network_parameter)) {
			echo "<option value=\"$product_network_parameter\">$product_network_parameter</option>";
		}
	}
	echo '</select></td></tr><tr><td>';
} catch (Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "<br>";
}



// highavailable ?
echo 'HA</td><td><select name="cr_ha_req" size="1">';
echo "<option value=\"0\">disabled</option>";
echo "<option value=\"1\">enabled</option>";
echo '</select></td></tr><tr><td>';

// ######################### form provision end ################################
echo '</td><td>';
echo "<input type=hidden name='action' value='provision'>";
echo "<input type=submit value='Provision'>";
echo "</p>";
echo "</form>";

echo "</tr></table>";

// ######################### form de-provision start ###########################
echo "<hr>";
echo "<h4>De-Provisioning / Set Cloud Request Status</h4>";

// ######################### Cloud method example ##############################

// get a list of all requests per user (or all if no username is given)
$cloudrequestgetlist_parameter = "user,$cloud_user,$cloud_password,$cloud_user";
$cloudrequest_list = '';
try {
	$cloudrequest_list = $client->CloudRequestGetList($cloudrequestgetlist_parameter);
} catch (Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "<br>";
}
if (is_array($cloudrequest_list)) {
	foreach($cloudrequest_list as $cr_id) {
		// de-provision the request / set request status
		echo "<form action=$thisfile method=post>";
		echo "<nobr><pre>";
		$cloudrequestgetlist_parameter = "user,$cloud_user,$cloud_password,$cr_id";
		try {
			$cloudrequest_array = $client->CloudRequestGetDetails($cloudrequestgetlist_parameter);
			print_r($cloudrequest_array);
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "<br>";
		}
		echo "</pre></nobr>";
		echo "<input type=hidden name='cr_id' value=\"$cr_id\">";
		echo "<input type=submit name='action' value='deprovision'>";
		echo "</form>";
		echo "<br>";
	}
}

// ######################### form de-provision end #############################
echo "<hr>";

// ######################### Get Cloud Users CCUs ##############################
$cloudusergetccus_parameter = "user,$cloud_user,$cloud_password,$cloud_user";
try {
	$cloud_user_ccunits = $client->CloudUserGetCCUs($cloudusergetccus_parameter);
	echo "<br>";
	echo "Cloud User $cloud_user has $cloud_user_ccunits CCUs";
} catch (Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "<br>";
}

echo "<br>";


// ######################### Get Cloud Users details ############################

echo "<hr>";

echo "<h4>Cloud User Details</h4>";
$cloudusergetdetails_parameter = "user,$cloud_user,$cloud_password,$cloud_user";
try {
	$cloud_user_details = $client->CloudUserGetDetails($cloudusergetdetails_parameter);
	echo "Cloud Details for User $cloud_user :";
	echo "<pre>";
	print_r($cloud_user_details);
	echo "</pre><br>";

	echo "<hr>";
	echo "<h4>Update Cloud User Details</h4>";

	$cr_user_lastname = $cloud_user_details['lastname'];
	$cr_user_forename = $cloud_user_details['forename'];
	$cr_user_email = $cloud_user_details['email'];
	$cr_user_street = $cloud_user_details['street'];
	$cr_user_city = $cloud_user_details['city'];
	$cr_user_country = $cloud_user_details['country'];
	$cr_user_phone = $cloud_user_details['phone'];

	// ######################### Set Cloud Users details ############################

	echo "<form action=$thisfile method=post>";
	echo "<p>";

	echo "Lastname <input type=text name='cr_user_lastname' value=\"$cr_user_lastname\">";
	echo "<br>";
	echo "Forename <input type=text name='cr_user_forename' value=\"$cr_user_forename\">";
	echo "<br>";
	echo "Email <input type=text name='cr_user_email' value=\"$cr_user_email\">";
	echo "<br>";
	echo "Street <input type=text name='cr_user_street' value=\"$cr_user_street\">";
	echo "<br>";
	echo "City <input type=text name='cr_user_city' value=\"$cr_user_city\">";
	echo "<br>";
	echo "Country <input type=text name='cr_user_country' value=\"$cr_user_country\">";
	echo "<br>";
	echo "Phone <input type=text name='cr_user_phone' value=\"$cr_user_phone\">";
	echo "<br>";
	echo "<input type=hidden name='action' value='update_user'>";
	echo "<input type=submit value='Update'>";
	echo "</p>";
	echo "</form>";

	// ######################### Set Cloud Users password ############################

	echo "<form action=$thisfile method=post>";
	echo "<p>";

	echo "Password <input type=text name='cr_user_password' value=\"\">";
	echo "<input type=hidden name='action' value='update_password'>";
	echo "<input type=submit value='Set Password'>";
	echo "</p>";
	echo "</form>";


} catch (Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "<br>";
}


// ######################### Get Cloud Users limits ############################

echo "<hr>";

echo "<h4>Cloud User Limits</h4>";
$cloudusergetlimits_parameter = "user,$cloud_user,$cloud_password,$cloud_user";
try {
	$clouduser_details = $client->CloudUserGetLimits($cloudusergetlimits_parameter);
	echo "Cloud Limits for User $cloud_user :";
	echo "<pre>";
	print_r($clouduser_details);
	echo "</pre><br>";
} catch (Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "<br>";
}


echo "<hr><br>";


// ######################### Get Cloud Users Transactions ############################

echo "<hr>";

echo "<h4>Cloud User Transactions</h4>";
$cloudusergettransaction_parameter = "user,$cloud_user,$cloud_password,$cloud_user,5";
try {
	$clouduser_details = $client->CloudUserGetTransactions($cloudusergettransaction_parameter);
	echo "Last 5 Cloud Transactions for User $cloud_user :";
	echo "<pre>";
	print_r($clouduser_details);
	echo "</pre><br>";
} catch (Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "<br>";
}


echo "</tr></table>";
echo "</form>";



// ######################### form Cloud User end ###############################
echo "<hr><br>";


// ######################### Cloud appliance method example ##############################

echo "<hr>";

echo "<h4>Cloud Appliances</h4>";

// get a list of all appliances per user (or all if no username is given)
$cloudappliancegetlist_parameter = "user,$cloud_user,$cloud_password,$cloud_user";
$cloudappliance_list = '';
try {
	$cloudappliance_list = $client->CloudApplianceGetList($cloudappliancegetlist_parameter);
} catch (Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "<br>";
}

if (is_array($cloudappliance_list)) {
	foreach($cloudappliance_list as $ca_id) {
		echo "<form action=$thisfile method=post>";
		// de-provision the appliance / set appliance status
		echo "<nobr><pre>";
		$cloudappliancegetdetails_parameter = "user,$cloud_user,$cloud_password,$ca_id";
		try {
			$cloudappliance_array = $client->CloudApplianceGetDetails($cloudappliancegetdetails_parameter);
			print_r($cloudappliance_array);

		// ######################### Cloud sshterm example ##############################
			$cloudappliance_ip = $cloudappliance_array['cloud_appliance_ip'];
			echo "<input type=hidden name='cr_cloudappliance_ip' value=\"$cloudappliance_ip\">";
			echo "ssh-login example <input type=submit name='action' value='login'>";
		// ######################### Cloud sshterm example ##############################
			echo "<br>";
		// ######################### Cloud appliance command example ##############################
			$cloudappliance_id = $cloudappliance_array['id'];
			echo "<input type=hidden name='cr_cloudappliance_id' value=\"$cloudappliance_id\">";
			echo "pause example <input type=submit name='action' value='appliance_pause'>";
			echo "<br>";
			echo "pause example <input type=submit name='action' value='appliance_unpause'>";
			echo "<br>";
			echo "pause example <input type=submit name='action' value='appliance_restart'>";
			echo "<br>";
			echo "update comment <input type=text name='cr_appliance_comment[$cloudappliance_id]' value='$cloudappliance_array[appliance_comment]'><input type=submit name='action' value='appliance_comment'>";
			echo "<br>";

		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "<br>";
		}
		echo "</pre></nobr>";
		echo "<br>";
		echo "</form>";
	}
}




// ######################### Cloud sshterm helper function start ###############
function sshterm_login($ip) {
	$OPENQRM_PLUGIN_AJAXTERM_REVERSE_PROXY_PORT=44322;
	$redirect_url="https://$ip:$OPENQRM_PLUGIN_AJAXTERM_REVERSE_PROXY_PORT";
	$left=50+($id*50);
	$top=100+($id*50);
?>
	<script type="text/javascript">
	function open_sshterm (url) {
		sshterm_window = window.open(url, "<?php echo $ip; ?>", "width=580,height=420,left=<?php echo $left; ?>,top=<?php echo $top; ?>");
		open_sshterm.focus();
	}
	open_sshterm("<?php echo $redirect_url; ?>");
	</script>
<?php

}
// ######################### Cloud sshterm helper function end #################


echo "<hr><br>";







?>
