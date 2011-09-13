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
$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/include/htmlobject.inc.php";



function documentation_introduction() {

	$disp = "<h1>介绍</h1>";
	$disp = $disp."<br>";
	$disp = $disp."本文档介绍OpenQRM数据中心管理平台。";
	$disp = $disp."<br>";
	$disp = $disp."<br>";

	$disp = $disp."<ul>";
	$disp = $disp."<li>";
	$disp = $disp."<a href='glossary.php'>OpenQRM的名词解释</a>";
	$disp = $disp."</li><li>";
	$disp = $disp."<a href='concept.php'>OpenQRM的设计理念</a> ";
	$disp = $disp."</li><li>";
	$disp = $disp."<a href='architecture.php'>OpenQRM的系统构架</a>";
	$disp = $disp."</li><li>";

	$disp = $disp."<a href='http://www.openqrm-enterprise.com/news/details/article/in-depth-documentation-of-openqrm-available.html' target='_BLANK'>OpenQRM的高级文档</a>";
	$disp = $disp."</li><li>";

	$disp = $disp."<a href='requirements.php'>OpenQRM的安装要求</a>,";
	$disp = $disp."</li><li>";
	$disp = $disp."<a href='installation.php'>OpenQRM的安装配置</a>,";
	$disp = $disp."</li><li>";
	$disp = $disp."<a href='plugins.php'>OpenQRM的插件集成</a>";
	$disp = $disp."</li><li>";
	$disp = $disp."<a href='howtos.php'>OpenQRM的实战教程</a>";
	$disp = $disp."</li><li>";
	$disp = $disp."<a href='quickstart.php'>OpenQRM的快速入门</a>.";
	$disp = $disp."</li><li>";
	$disp = $disp."<a href='development.php'>OpenQRM的二次开发</a>.";
	$disp = $disp."</li>";
	$disp = $disp."</ul>";
	$disp = $disp."<br>";

	$disp = $disp."<br>";
	return $disp;
}




$output = array();
$output[] = array('label' => '介绍', 'value' => documentation_introduction());

?>
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
<link rel="stylesheet" type="text/css" href="documentation.css" />
<?php
echo htmlobject_tabmenu($output);
?>
