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



function documentation_requirements() {

	$disp = "<h1>安装要求</h1>";
	$disp = $disp."<br>";
	$disp = $disp."OpenQRM使用了可扩展的构架设计，可以利用远程数据库和远程服务器进行分布式安装。本章节简单介绍对标准安装和高级安装的系统要求。";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."标准安装：";
	$disp = $disp."<ul>";
	$disp = $disp."<li>";
	$disp = $disp."一台专用的Linux服务器，用于安装OpenQRM服务";
	$disp = $disp."</li><li>";
	$disp = $disp."一台或者多台被OpenQRM所管理的计算节点";
	$disp = $disp."<br>";
	$disp = $disp."（在服务器资源有限的情况下，也可以使用全虚拟化的系统 - 例如VMWare或者QEMU虚拟机 - 来作为计算节点。）";
	$disp = $disp."</li>";
	$disp = $disp."</ul>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."高级安装（推荐）：";
	$disp = $disp."<ul>";
	$disp = $disp."<li>";
	$disp = $disp."一台专用的Linux服务器，用于安装OpenQRM服务";
	$disp = $disp."<br>";
	$disp = $disp."（如果要求OpenQRM服务的高可用性，则需要两台专用的OpenQRM服务器。）";
	$disp = $disp."</li><li>";
	$disp = $disp."一台高可用的数据库服务器（远程）";
	$disp = $disp."</li><li>";
	$disp = $disp."一台或者多台高可用的存储服务器";
	$disp = $disp."</li><li>";
	$disp = $disp."一台或者多台被OpenQRM所管理的计算节点";
	$disp = $disp."</li>";
	$disp = $disp."</ul>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."对OpenQRM服务器的一般性要求：";
	$disp = $disp."<ul>";
	$disp = $disp."<li>";
	$disp = $disp."1 GB 内存（或者更多）";
	$disp = $disp."</li><li>";
	$disp = $disp."一个数据库（MySQL, Postgres, Oracle或者DB2）";
	$disp = $disp."</li>";
	$disp = $disp."</ul>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."在安装过程中遇到的软件依赖性问题，会由OpenQRM安装程序自动解决。";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	return $disp;
}




$output = array();
$output[] = array('label' => '安装要求', 'value' => documentation_requirements());

?>
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
<link rel="stylesheet" type="text/css" href="documentation.css" />
<?php
echo htmlobject_tabmenu($output);
?>
