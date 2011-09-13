
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
<style>
.htmlobject_tab_box {
	width:700px;
}
</style>

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

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/include/htmlobject.inc.php";


function tftpd_about() {
	global $OPENQRM_SERVER_BASE_DIR;
	$disp = "<h1><img border=0 src=\"/openqrm/base/plugins/tftpd/img/plugin.png\">TFTP插件</h1>";
	$disp = $disp."<br>";
	$disp = $disp."CloudPro的快速部署方法使用PXE技术从网络启动计算资源，负责从网络提供操作系统组件的TFTP服务是启动计算资源的必要条件。";
	$disp = $disp."本插件提供了一个自适应的TFTP服务器，为CloudPro所管理的计算资源提供内核以及操作系统映像。";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<b>使用方法：</b>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."本插件无需手工配置。插件被启动时，本插件自动启动TFTP服务。";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	return $disp;
}


$output = array();
$output[] = array('label' => '插件介绍', 'value' => tftpd_about());
echo htmlobject_tabmenu($output);

?>


