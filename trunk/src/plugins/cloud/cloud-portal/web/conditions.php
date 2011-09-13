<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
<title>CloudPro云计算门户</title>
<link rel="stylesheet" type="text/css" href="css/mycloud.css" />

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
require_once "$RootDir/include/htmlobject.inc.php";


function terms_and_condition() {

	global $OPENQRM_USER;
	global $thisfile;

	$disp = $disp."<h1><b>CloudPro云计算服务条款</b></h1>";
	$disp = $disp."<br>";
	$disp = $disp."所有使用CloudPro云计算服务的用户，必须遵守如下条款：";
	$disp = $disp."<ol>";

	$disp = $disp."<li>（1）不利用本系统所提供的计算能力进行任何非法活动。</li>";
	$disp = $disp."<li>（2）仅通过本系统Web 界面所提供的“我的器件”功能管理您的计算资源。</li>";
	$disp = $disp."<li>（3）请勿使用命令行方式重启或者关闭计算资源。</li>";
	$disp = $disp."<li>（4）请勿关闭您的器件中的openQRM服务。</li>";
	$disp = $disp."<li>（5）请勿停用或者重新配置本系统任何计算资源的网卡。</li>";
	$disp = $disp."</ol>";

	$disp = $disp."除此之外，您可以自由地享受CloudPro给您所带来的便利。";
	$disp = $disp."<br>";
	$disp = $disp."<hr>";

	return $disp;
}



$output = array();

// include header
include "$DocRoot/cloud-portal/mycloud-head.php";

$output[] = array('label' => 'CloudPro云计算服务条款', 'value' => terms_and_condition());
echo htmlobject_tabmenu($output);

// include footer
include "$DocRoot/cloud-portal/mycloud-bottom.php";

?>

</html>







