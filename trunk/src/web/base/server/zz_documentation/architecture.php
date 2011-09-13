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



function documentation_architecture() {

	$disp = "<h1>系统构架</h1>";
	$disp = $disp."<br>";
	$disp = $disp."在<a href='concept.php'>设计理念</a>中我们提到，管理数据中心的所有设备、功能与服务是一个严峻的任务，已经超出了一个专有应用程序的处理能力。";
	$disp = $disp."如果不能完美集成所有的组件，自动化和高可用性就成了一句空谈。";
	$disp = $disp."其结果就是让数据中心变得越来越复杂。";
	$disp = $disp."<br><br>";
	$disp = $disp."To solve this problem openQRM is based on an strictly plugg-able architecture !";
	$disp = $disp."<br><br>";
	$disp = $disp."The openQRM-server is separated into 'base' and 'plugins' and actually the base more or less 'just' manages the plugins.";
	$disp = $disp." The 'base' also provides the framework for the plugins to interact with (e.g. resource, image, storage, ... objects) but";
	$disp = $disp." all the features of openQRM are provided by its plugins.";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."This has several benefits :";

	$disp = $disp."<ul>";
	$disp = $disp."<li>";
	$disp = $disp."rapid development because developers can work in paralell on different plugins";
	$disp = $disp."</li><li>";
	$disp = $disp."enhanced robustness because of a robust base which does not change much and often";
	$disp = $disp."</li><li>";
	$disp = $disp."easy integration of third-party components via a well defined plugin-API";
	$disp = $disp."</li><li>";
	$disp = $disp."bugs in a plugin does not harm the base system";
	$disp = $disp."</li><li>";
	$disp = $disp."less complexity because the plugin manages just its own environment";
	$disp = $disp."</li><li>";
	$disp = $disp."less code in the base-engine, less code means less bugs";
	$disp = $disp."</li><li>";
	$disp = $disp."better scalability because plugins can be enabled/disabled on the fly";
	$disp = $disp."</li><li>";
	$disp = $disp."plugins are easy to develop because of the provided base-framework";
	$disp = $disp."</li>";
	$disp = $disp."</ul>";
	$disp = $disp."<br>";

	return $disp;
}




$output = array();
$output[] = array('label' => '系统构架', 'value' => documentation_architecture());

?>
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
<link rel="stylesheet" type="text/css" href="documentation.css" />
<?php
echo htmlobject_tabmenu($output);
?>
