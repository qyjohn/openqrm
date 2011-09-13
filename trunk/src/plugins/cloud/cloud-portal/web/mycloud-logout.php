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


function mycloud_logout() {
	global $DocRoot;
	if (!isset($_GET['quit'])) {
		// include header
		include "$DocRoot/cloud-portal/mycloud-head.php";
		$disp = "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/mycloud.css\" />";
		$disp .= "<style>";
		$disp .= ".htmlobject_tab_box {";
		$disp .= "width:600px\;";
		$disp .= "}";
		$disp .= "</style>";
		$disp .= "<h4>请进入<a href=\"mycloud-logout.php?quit=y\">登录界面</a>，首先点击'OK'按钮，然后点击'Cancel'按钮，才能够正确地登离系统。";
		$disp .= "<br><br>";
		$disp .= "在此过程中请务必不要输入您的密码!";
		$disp .= "<br><br>";
		$disp .= "这样您的帐号和密码就不会被保存在浏览器的缓存中";
		$disp .= "<br><br>";
		$disp .= "<p>返回<a href=\"/cloud-portal/user/mycloud.php\">CloudPro云计算服务门户</a>。</h4>";
	} else {
		header('WWW-Authenticate: Basic realm="This Realm"');
		header('HTTP/1.0 401 Unauthorized');
		// if a session was running, clear and destroy it
		session_start();
		session_unset();
		session_destroy();

		// include header
		include "$DocRoot/cloud-portal/mycloud-head.php";
		$disp = "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/mycloud.css\" />";
		$disp .= "<style>";
		$disp .= ".htmlobject_tab_box {";
		$disp .= "width:600px;";
		$disp .= "}";
		$disp .= "</style>";
		$disp .= "<h3>成功登离系统!</h3>";
		$disp .= "<br><br>";
		$disp .= "<h4>返回<a href=\"/cloud-portal/\">CloudPro云计算服务门户</a>。</h4>";
	}
	return $disp;

}


$output = array();
$output[] = array('label' => '登离系统', 'value' => mycloud_logout());
echo htmlobject_tabmenu($output);

// include footer
include "$DocRoot/cloud-portal/mycloud-bottom.php";


?>
