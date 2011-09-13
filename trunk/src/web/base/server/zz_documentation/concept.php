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



function documentation_concept() {

	$disp = "<h1>设计理念</h1>";
	$disp = $disp."<br>";
	$disp = $disp."OpenQRM的理念是将数据中心内的所有设备抽象成不同的模块。因此，对数据中心的管理就变成了对模块组合的管理。";
	$disp = $disp."这听起来似乎令人困惑，不过这一切都是比较容易理解的。";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."通常来说，一个数据中心提供如下设备和功能：";
	$disp = $disp."<ul>";
	$disp = $disp."<li>";
	$disp = $disp."物理硬件";
	$disp = $disp."</li><li>";
	$disp = $disp."操作系统";
	$disp = $disp."</li><li>";
	$disp = $disp."应用与服务";
	$disp = $disp."</li><li>";
	$disp = $disp."交换机";
	$disp = $disp."</li><li>";
	$disp = $disp."服务协议（Service Level Agreements, SLA）";
	$disp = $disp."</li><li>";
	$disp = $disp."存储";
	$disp = $disp."</li><li>";
	$disp = $disp."虚拟化技术 / 虚拟硬件";
	$disp = $disp."</li><li>";
	$disp = $disp."监控";
	$disp = $disp."</li><li>";
	$disp = $disp."高可用性";
	$disp = $disp."</li><li>";
	$disp = $disp."系统安装和部署";
	$disp = $disp."</li><li>";
	$disp = $disp."资源规划和开通";
	$disp = $disp."</li><li>";
	$disp = $disp."自动化";
	$disp = $disp."</li><li>";
	$disp = $disp."以及其他服务和功能";
	$disp = $disp."</li>";
	$disp = $disp."</ul>";
	$disp = $disp."<br>";
	$disp = $disp."通过一个应用来管理所有这些不同的设备、功能、协议是困难的。";
	$disp = $disp."但是，通过OpenQRM的可扩展构架，不同的功能模块能够通过API的方式被集成到同一个用户界面中。";
	$disp = $disp."<br><br>";
	$disp = $disp."OpenQRM的强项，在于管理大规模的Linux服务器环境。因此，我们深入地了解一下Linux系统的组成。";
	$disp = $disp."<br><br>";
	$disp = $disp."Linux系统都有什么？";

	$disp = $disp."<ul>";
	$disp = $disp."<li>";
	$disp = $disp."内核文件（vmlinuz）";
	$disp = $disp."</li><li>";
	$disp = $disp."虚拟磁盘文件（initrd.img）";
	$disp = $disp."</li><li>";
	$disp = $disp."内核模块文件（/lib/modules/[kernel-version]/）";
	$disp = $disp."</li><li>";
	$disp = $disp."根文件系统（/）";
	$disp = $disp."</li>";
	$disp = $disp."</ul>";
	$disp = $disp."<br>";
	$disp = $disp."也就是说，一个Linux系统不过是一堆文件而已。";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."如果说Linux系统不过是一堆文件，那么我们就应该把它当作文件来看待。";
	$disp = $disp."<br>";
	$disp = $disp."因此，OpenQRM的快速部署机制，就是将服务器打包成文件，并利用了现代存储技术中的快速克隆、快照等功能来管理这些文件。";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."在此基础上，OpenQRM将部署器件的过程抽象成将预先制作好的虚拟机映像部署到物理硬件或者是虚拟硬件的过程。";
	$disp = $disp."";
	$disp = $disp."";
	$disp = $disp."";
	$disp = $disp."";
	$disp = $disp."";
	return $disp;
}




$output = array();
$output[] = array('label' => '设计理念', 'value' => documentation_concept());

?>
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
<link rel="stylesheet" type="text/css" href="documentation.css" />
<?php
echo htmlobject_tabmenu($output);
?>
