<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
<title>CloudPro云计算门户</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></meta>
<link rel="stylesheet" type="text/css" href="css/mycloud.css" />
<style>
.htmlobject_tab_box {
	width:600px;
}
</style>

</head>
<body>

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
$CloudDir = $_SERVER["DOCUMENT_ROOT"].'/cloud-portal/';
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
require_once "$RootDir/plugins/cloud/class/cloudrequest.class.php";
require_once "$RootDir/plugins/cloud/class/cloudconfig.class.php";
require_once "$RootDir/plugins/cloud/class/cloudmailer.class.php";

$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $OPENQRM_SERVER_BASE_DIR;
$refresh_delay=5;
global $CLOUD_USER_TABLE;
global $event;

// the location of the howto for the cloud portal
$cloud_portal_howto="http://www.openqrm.com/?q=node/139";

// gather user parameter in array
foreach ($_REQUEST as $key => $value) {
	if (strncmp($key, "cu_", 3) == 0) {
		$user_fields[$key] = $value;
	}
}


function redirect($strMsg, $currenttab = 'tab0', $url = '') {
	global $thisfile;
	if($url == '') {
		$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab='.$currenttab;
	}
	//	using meta refresh here because the appliance and resourc class pre-sending header output
	echo "<meta http-equiv=\"refresh\" content=\"0; URL=$url\">";
}


function is_allowed($text) {
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
	global $c_error;
	if (!strlen($value)) {
		$strMsg = "$param is empty <br>";
		$c_error = 1;
		redirect($strMsg, tab0);
		exit(0);
	}
	// remove whitespaces
	$value = trim($value);
	// remove any non-violent characters
	$value = str_replace(".", "", $value);
	$value = str_replace(",", "", $value);
	$value = str_replace("-", "", $value);
	$value = str_replace("_", "", $value);
	$value = str_replace("(", "", $value);
	$value = str_replace(")", "", $value);
	$value = str_replace("/", "", $value);
	if(!is_allowed($value)){
		$strMsg = "$param contains special characters <br>";
		$c_error = 1;
		redirect($strMsg, tab0);
		exit(0);
	}
}

// register action

if(htmlobject_request('action') != '') {
	switch (htmlobject_request('action')) {
		case 'create_user':
			$c_error = 0;
			// checks
			check_param("Username", $user_fields['cu_name']);
			check_param("Password", $user_fields['cu_password']);

			check_param("Lastname", $user_fields['cu_lastname']);
			check_param("Forename", $user_fields['cu_forename']);
			check_param("Street", $user_fields['cu_street']);
			check_param("City", $user_fields['cu_city']);
			check_param("Country", $user_fields['cu_country']);
			check_param("Phone", $user_fields['cu_phone']);

			// email valid ?
			$cloud_email = new clouduser();
			if (!$cloud_email->checkEmail($user_fields['cu_email'])) {
				$strMsg = "Email address is invalid. <br>";
				$c_error = 1;
				redirect($strMsg, tab1);
				exit(0);
			}

			// password equal ?
			if (strcmp($user_fields['cu_password'], $user_fields['cu_password_check'])) {
				$strMsg = "Passwords are not equal <br>";
				$c_error = 1;
				redirect($strMsg, tab1);
				exit(0);
			}
			// password min 6 characters
			if (strlen($user_fields['cu_password'])<6) {
				$strMsg .= "Password must be at least 6 characters long <br>";
				$c_error = 1;
				redirect($strMsg, tab1);
				exit(0);
			}
			// username min 4 characters
			if (strlen($user_fields['cu_name'])<4) {
				$strMsg .= "Username must be at least 4 characters long <br>";
				$c_error = 1;
				redirect($strMsg, tab1);
				exit(0);
			}
			// does username already exists ?
			$c_user = new clouduser();
			if (!$c_user->is_name_free($user_fields['cu_name'])) {
				$uname = $user_fields['cu_name'];
				$strMsg .= "A user with the name $uname already exist. Please choose another username <br>";
				$c_error = 1;
				redirect($strMsg, tab1);
				exit(0);
			}

			if ($c_error == 0) {
				$user_name = $user_fields['cu_name'];
				$strMsg = "Creating user $user_name <br>Please check your email to activate your account.<br>";

				// create token
				$user_token = md5(uniqid(rand(), true));
				$user_fields['cu_token'] = $user_token;
				// prepare more defaults
				$user_fields['cu_status'] = 0;
				// default user group
				$user_fields['cu_cg_id'] = 0;
				$user_fields['cu_id'] = openqrm_db_get_free_id('cu_id', $CLOUD_USER_TABLE);
				// check how many ccunits to give for a new user
				$cc_conf = new cloudconfig();
				$cc_auto_give_ccus = $cc_conf->get_value(12);  // 12 is auto_give_ccus
				$user_fields['cu_ccunits'] = $cc_auto_give_ccus;
				$cl_user = new clouduser();
				// add user
				$cl_user->add($user_fields);

				// mail user
				// get admin email
				$cc_admin_email = $cc_conf->get_value(1);  // 1 is admin_email
				// get external name
				$external_portal_name = $cc_conf->get_value(3);  // 3 is the external name
				if (!strlen($external_portal_name)) {
					$external_portal_name = "http://$OPENQRM_SERVER_IP_ADDRESS/cloud-portal";
				}
				$email = $user_fields['cu_email'];
				$forename = $user_fields['cu_forename'];
				$lastname = $user_fields['cu_lastname'];
				$cuid = $user_fields['cu_id'];
				$rmail = new cloudmailer();
				$rmail->to = "$email";
				$rmail->from = "$cc_admin_email";
				$rmail->subject = "openQRM Cloud: Activate your account";
				$rmail->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/activate_new_cloud_user.mail.tmpl";
				$arr = array('@@USER@@'=>"$username", '@@ID@@'=>"$cuid", '@@TOKEN@@'=>"$user_token", '@@EXTERNALPORTALNAME@@'=>"$external_portal_name", '@@FORENAME@@'=>"$forename", '@@LASTNAME@@'=>"$lastname");
				$rmail->var_array = $arr;
				$rmail->send();

				redirect($strMsg, tab0);
			}

			break;

		case 'activate':

			$event->log("cloud-portal", $_SERVER['REQUEST_TIME'], 5, "index.php", "Processing activate user command", "", "", 0, 0, 0);

			$u_error = 0;
			$cu_id = $_REQUEST['cu_id'];
			$cu_token_post = $_REQUEST['cu_token'];
			check_param("cu_id", $cu_id);
			check_param("cu_token_post", $cu_token_post);

			$cloud_user = new clouduser();
			$cloud_user->get_instance_by_id($cu_id);
			// some checks

			// already activated ?
			if ($cloud_user->status == 1) {
				$event->log("cloud-portal", $_SERVER['REQUEST_TIME'], 2, "index.php", "User $cu_id already activated!", "", "", 0, 0, 0);
				$strMsg .= "User already actiavted ... <br>";
				$u_error = 1;
				redirect($strMsg, tab0);
				exit(0);
			}

			$cu_token_db = $cloud_user->token;
			if (!strlen($cu_token_db)) {
				$event->log("cloud-portal", $_SERVER['REQUEST_TIME'], 2, "index.php", "Got emtpy token for user activation!", "", "", 0, 0, 0);
				$strMsg .= "No token found. Aborting ... <br>";
				$u_error = 1;
				redirect($strMsg, tab0);
				exit(0);
			}
			// verify the token
			if (strcmp($cu_token_db, $cu_token_post)) {
				$event->log("cloud-portal", $_SERVER['REQUEST_TIME'], 2, "index.php", "Got invalid token for user activation!", "", "", 0, 0, 0);
				$strMsg .= "Warning, invalid token. Aborting ... $cu_token_db -- $cu_token_post <br>";
				$u_error = 1;
				redirect($strMsg, tab0);
				exit(0);
			}

			// enable the user
			if ($u_error == 0) {

				$event->log("cloud-portal", $_SERVER['REQUEST_TIME'], 5, "index.php", "Enabling the user $cu_id", "", "", 0, 0, 0);
				$cloud_user->activate_user_status($cu_id, 1);
				// add user to htpasswd
				$username = $cloud_user->name;
				$password = $cloud_user->password;
				$cloud_htpasswd = "$CloudDir/user/.htpasswd";
				if (file_exists($cloud_htpasswd)) {
					$openqrm_server_command="htpasswd -b $CloudDir/user/.htpasswd $username $password";
				} else {
					$openqrm_server_command="htpasswd -c -b $CloudDir/user/.htpasswd $username $password";
				}
				$output = shell_exec($openqrm_server_command);
				$event->log("cloud-portal", $_SERVER['REQUEST_TIME'], 5, "index.php", "User $cu_id added to the htpasswd", "", "", 0, 0, 0);

				// mail again that account is active now
				$cc_conf = new cloudconfig();
				$cc_admin_email = $cc_conf->get_value(1);  // 1 is admin_email
				// get external name
				$external_portal_name = $cc_conf->get_value(3);  // 3 is the external name
				if (!strlen($external_portal_name)) {
					$external_portal_name = "http://$OPENQRM_SERVER_IP_ADDRESS/cloud-portal";
				}

				$email = $cloud_user->email;
				$forename = $cloud_user->forename;
				$lastname = $cloud_user->lastname;
				$rmail = new cloudmailer();
				$rmail->to = "$email";
				$rmail->from = "$cc_admin_email";
				$rmail->subject = "openQRM Cloud: Your account has been activated";
				$rmail->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/welcome_new_cloud_user.mail.tmpl";
				$arr = array('@@USER@@'=>"$username", '@@PASSWORD@@'=>"$password", '@@EXTERNALPORTALNAME@@'=>"$external_portal_name", '@@FORENAME@@'=>"$forename", '@@LASTNAME@@'=>"$lastname", '@@CLOUDADMIN@@'=>"$cc_admin_email");
				$rmail->var_array = $arr;
				$rmail->send();

				$event->log("cloud-portal", $_SERVER['REQUEST_TIME'], 5, "index.php", "Send mail to User $cu_id", "", "", 0, 0, 0);

				$strMsg = "Your account has been activate. You can now login to the openQRM Cloud.<br>";
				redirect($strMsg, tab0);
			}

			break;

		case 'forgotpass':

			$fusername = $_REQUEST['fusername'];
			check_param("fusername", $fusername);

			$cloud_user = new clouduser();
			if ($cloud_user->is_name_free($fusername)) {
				$strMsg = "No such user on the openQRM Cloud";
				redirect($strMsg, tab0);
				break;
			}

			$cloud_user->get_instance_by_name($fusername);
			// mail again that account is active now
			$cc_conf = new cloudconfig();
			$cc_admin_email = $cc_conf->get_value(1);  // 1 is admin_email
			// get external name
			$external_portal_name = $cc_conf->get_value(3);  // 3 is the external name
			if (!strlen($external_portal_name)) {
				$external_portal_name = "http://$OPENQRM_SERVER_IP_ADDRESS/cloud-portal";
			}
			$email = $cloud_user->email;
			$forename = $cloud_user->forename;
			$lastname = $cloud_user->lastname;
			$username = $cloud_user->name;

			// generate a new password
			$image_tmp = new image();
			$password = $image_tmp->generatePassword(8);
			// remove old user
			$openqrm_server_command="htpasswd -D $CloudDir/user/.htpasswd $username";
			$output = shell_exec($openqrm_server_command);
			// create new + new password
			$openqrm_server_command="htpasswd -b $CloudDir/user/.htpasswd $username $password";
			$output = shell_exec($openqrm_server_command);

			// set the new password in the db
			$cloud_user->set_users_password($cloud_user->id, $password);

			$rmail = new cloudmailer();
			$rmail->to = "$email";
			$rmail->from = "$cc_admin_email";
			$rmail->subject = "openQRM Cloud: Your password has been reseted";
			$rmail->template = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/cloud/etc/mail/your_password_has_been_reseted.tmpl";
			$arr = array('@@USER@@'=>"$username", '@@PASSWORD@@'=>"$password", '@@EXTERNALPORTALNAME@@'=>"$external_portal_name", '@@FORENAME@@'=>"$forename", '@@LASTNAME@@'=>"$lastname");
			$rmail->var_array = $arr;
			$rmail->send();

			$strMsg = "Your password on the openQRM Cloud has been reseted and sent to you. Please check your mailbox.";
			redirect($strMsg, tab0);

			break;

	}
}





function portal_home() {

	global $OPENQRM_USER;
	global $thisfile;
	global $cloud_portal_howto;

	$disp = "<h1>OpenQRM 云计算门户</h1>";
	$disp = $disp."OpenQRM云计算门户，随时满足您对计算资源的需求。";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."使用流程：";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."- 首先，<a href=\"$thisfile?currenttab=tab1\">注册</a>一个账号";
	$disp = $disp."<br>";
	$disp = $disp."- 您会收到一封电子邮件要求您激活帐号";
	$disp = $disp."<br>";
	$disp = $disp."- <a href=\"$thisfile?activate=yes\">激活</a>您的帐号";
	$disp = $disp."<br>";
	$disp = $disp."- 获得一些云计算币（CCU, Cloud Computing Units）";
	$disp = $disp."<br>";
	$disp = $disp."- 通过云计算门户请求计算资源";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."开始使用！";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."更多使用方面的问题，请参考我们提供的 <a href=\"$cloud_portal_howto\" target=\"_BLANK\">实战教程</a>。";
	$disp = $disp."<br>";

	return $disp;
}



function register_user() {

	global $OPENQRM_USER;
	global $thisfile;

	$cc_conf = new cloudconfig();
	$public_register_enabled = $cc_conf->get_value(14);  // 14 is public_register_enabled
	$cc_admin_email = $cc_conf->get_value(1);  // 1 is admin_email

	if ($public_register_enabled == 'true') {
		$disp = "<h1>用户注册</h1>";
		$disp = $disp."<br>";
		$disp = $disp."<form action=$thisfile method=post>";
		$disp = $disp.htmlobject_input('cu_name', array("value" => '[Username]', "label" => '注册帐号：'), 'text', 20);
		$disp = $disp.htmlobject_input('cu_password', array("value" => '', "label" => '设置密码：'), 'password', 20);
		$disp = $disp.htmlobject_input('cu_password_check', array("value" => '', "label" => '重输密码：'), 'password', 20);
		$disp = $disp.htmlobject_input('cu_forename', array("value" => '[Firstname]', "label" => '名：'), 'text', 50);
		$disp = $disp.htmlobject_input('cu_lastname', array("value" => '[Lastname]', "label" => '姓：'), 'text', 50);
		$disp = $disp.htmlobject_input('cu_email', array("value" => '[Email]', "label" => '电子邮件：'), 'text', 50);
		$disp = $disp.htmlobject_input('cu_street', array("value" => '[Street]', "label" => '街道地址：'), 'text', 100);
		$disp = $disp.htmlobject_input('cu_city', array("value" => '[City]', "label" => '所在城市：'), 'text', 100);
		$disp = $disp.htmlobject_input('cu_country', array("value" => '[Country]', "label" => '所在国家：'), 'text', 100);
		$disp = $disp.htmlobject_input('cu_phone', array("value" => '[Phone-number]', "label" => '电话号码：'), 'text', 100);
		$disp = $disp."<input type=hidden name='action' value='create_user'>";
		$disp = $disp."<b><i>所有字段都是必须的。请勿输入任何特殊字符。</i></b>";
		$disp = $disp."<br>";
		$disp = $disp."<br>";
		$disp = $disp."<input type=submit value='注册'>";
		$disp = $disp."<br>";
		$disp = $disp."我接受<a href=\"/cloud-portal/web/conditions.php\" target=\"_BLANK\">CloudPro云计算服务条款</a>的相关规定。";
		$disp = $disp."<br>";
		$disp = $disp."<br>";
		$disp = $disp."</form>";
	} else {
		$disp = "<h1>不提供公开注册服务!</h1>";
		$disp = $disp."<br>";
		$disp = $disp."本CloudPro云计算服务门户不提供公开注册服务。";
		$disp = $disp."<br>";
		$disp = $disp."请联系管理员<a href=\"mailto:$cc_admin_email?subject=openQRM Cloud: Account request\">$cc_admin_email</a>咨询创建帐号事宜。";
		$disp = $disp."<br>";
	}

	$disp = $disp."<form action=$thisfile method=post>";
	$disp = $disp."<br>";
	$disp = $disp."<hr>";
	$disp = $disp."<h4>忘记密码?</h4>";
	$disp = $disp."您已经注册了CloudPro云计算服务的帐号，但是忘记了您的密码？";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."请输入您的用户名，然后点击'取回密码'按钮。";
	$disp = $disp."CloudPro云计算服务会为您提供一个新的密码。";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp.htmlobject_input('fusername', array("value" => '[Username]', "label" => '注册帐号：'), 'text', 20);
	$disp = $disp."<input type=hidden name='action' value='forgotpass'>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<input type=submit value='取回密码'>";
	$disp = $disp."</form>";

	return $disp;
}



function activate_user() {

	global $OPENQRM_USER;
	global $thisfile;

	$disp = "<h1>激活帐号</h1>";
	$disp = $disp."<br>";
	$disp = $disp."<form action=$thisfile method=post>";
	$disp = $disp.htmlobject_input('cu_id', array("value" => '[Your-User-ID]', "label" => 'User ID'), 'text', 20);
	$disp = $disp.htmlobject_input('cu_token', array("value" => '[Your-secret-token]', "label" => 'Token'), 'text', 100);
	$disp = $disp."<input type=hidden name='action' value='activate'>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<input type=submit value='激活帐号'>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."</form>";

	return $disp;
}



function login_user() {

	global $OPENQRM_USER;
	global $thisfile;

	$disp = "<a href=\"/cloud-portal/user/mycloud.php\"><img src='img/forward.gif' width='36' height='32' border='0' alt='' align='left'>";
	$disp = $disp."<h1><b>登录进入CloudPro云计算门户</b></h1></a>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<hr>";
	return $disp;
}



$output = array();

// include header
include "$DocRoot/cloud-portal/mycloud-head.php";

// check if the cloud is enabled
$cc_config = new cloudconfig();
$cloud_enabled = $cc_config->get_value(15);	// 15 is cloud_enabled
if ($cloud_enabled != 'true') {	
	include "$DocRoot/cloud-portal/mycloud-disabled.php";
}

$activate = htmlobject_request('activate');
if (!strcmp($activate, "yes")) {
	$output[] = array('label' => "<a href=\"$thisfile?currenttab=tab0\">激活帐号</a>", 'value' => activate_user());
} else {
	$output[] = array('label' => "<a href=\"$thisfile?currenttab=tab0\">欢迎使用CloudPro云计算服务</a>", 'value' => portal_home());
	$output[] = array('label' => "<a href=\"$thisfile?currenttab=tab1\">注册</a>", 'value' => register_user());
	$output[] = array('label' => "<a href=\"/cloud-portal/user/mycloud.php\">登录</a>", 'value' => login_user());
}
echo htmlobject_tabmenu($output);

// include footer
include "$DocRoot/cloud-portal/mycloud-bottom.php";

?>








