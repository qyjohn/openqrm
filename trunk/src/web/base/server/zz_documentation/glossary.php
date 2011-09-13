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



function documentation_glossary() {

	$disp = "<h1>名词解释</h1>";
	$disp = $disp."<br>";
	$disp = $disp."本文档中所涉及的名词解释：";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<ul>";
	$disp = $disp."<li>";

	$disp = $disp."<b>器件（Appliance）</b>";
	$disp = $disp."<br>";
	$disp = $disp."- OpenQRM器件是内核，映像，计算资源，应用环境以及服务协议的组合。";
	$disp = $disp." 通常来说，器件可以认为是一个实现某种功能的服务。一个预先配置好的器件能够容易地通过鼠标点击来启动或者关闭。";
	$disp = $disp."</li><li>";

	$disp = $disp."<b>金映像（Golden Image）</b>";
	$disp = $disp."<br>";
	$disp = $disp."- 金映像是一个仅作为模版使用的虚拟机映像，其本身不会被直接部署到虚拟机上。OpenQRM的存储插件提供了通过快速克隆金映像制作新的映像的方法。";
	$disp = $disp."</li><li>";

	$disp = $disp."<b>映像（Image）</b>";
	$disp = $disp."<br>";
	$disp = $disp."- 服务器根文件系统的一个拷贝，可以存放在块设备（例如ISCSI和AOE）上或者是目录（例如NFS）下。";
	$disp = $disp."</li><li>";

	$disp = $disp."<b>内核（Kernel）</b>";
	$disp = $disp."<br>";
	$disp = $disp."- OpenQRM中的内核包括一个Linux内核（vmlinuz）、与之对应的内核模块（/lib/moduels/[kernel-version]）、系统映射表（System Map）、以及";
	$disp = $disp."一个特别的OpenQRM-initrd虚拟磁盘。其中，openQRM-initrd提供了与OpenQRM插件模块进行交互的接口。";
	$disp = $disp."</li><li>";

	$disp = $disp."<b>插件（Plugin）	</b>";
	$disp = $disp."<br>";
	$disp = $disp."- 插件为OpenQRM服务器提供额外的功能模块。OpenQRM插件通过OpenQRM框架所定义的API与OpenQRM服务器通讯，使得第三方的模块可以与OpenQRM服务器无缝集成。";
	$disp = $disp."</li><li>";

	$disp = $disp."<b>计算资源（Resource）</b>";
	$disp = $disp."<br>";
	$disp = $disp."- 数据中心里可以使用的物理硬件资源或者是虚拟硬件资源。";
	$disp = $disp."</li><li>";

	$disp = $disp."<b>存储（Storage）</b>";
	$disp = $disp."<br>";
	$disp = $disp."- 存储虚拟机映像的存储系统，可以包括NFS、ISCSI、AEO/Coraid、NetApp-Filer等多种类型。";

	$disp = $disp."</li>";
	$disp = $disp."</ul>";
	$disp = $disp."<br>";

	return $disp;
}




$output = array();
$output[] = array('label' => 'Glossary', 'value' => documentation_glossary());

?>
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
<link rel="stylesheet" type="text/css" href="documentation.css" />
<?php
echo htmlobject_tabmenu($output);
?>
