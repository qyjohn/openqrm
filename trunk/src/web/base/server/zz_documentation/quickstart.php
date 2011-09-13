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



function documentation_quickstart() {

	$disp = "<h1>快速入门</h1>";
	$disp = $disp."<br>";
	$disp = $disp."如何启动OpenQRM？我需要哪些插件？我需要哪些条件来安装一个最简单的系统？";
	$disp = $disp."<br>";
	$disp = $disp."按照下面的步骤，您可以迅速配置一个可以使用得最简单系统：";
	$disp = $disp."<ul>";
	$disp = $disp."<li>";
	$disp = $disp."启用并且启动一个或者多个存储（storage）插件";
	$disp = $disp."</li><li>";
	$disp = $disp."创建一个或者多个存储服务器";
	$disp = $disp."</li><li>";
	$disp = $disp."创建一个或者多个映像";
	$disp = $disp."</li><li>";
	$disp = $disp."启用并且启动dhcpd和tftpd插件";
	$disp = $disp."</li><li>";
	$disp = $disp."通过网络启动一台或者多台服务器";
	$disp = $disp."<br>";
	$disp = $disp."（在服务器的系统BIOS中设置为通过PXE/Network启动）";
	$disp = $disp."<br>";
	$disp = $disp."-> 通过网络系统的系统，会被自动添加到OpenQRM网络中。";
	$disp = $disp."</li><li>";
	$disp = $disp."选择一个空闲或者可用的计算资源，创建一个或者多个器件";
	$disp = $disp."</li><li>";
	$disp = $disp."启动器件";
	$disp = $disp."</li>";
	$disp = $disp."</ul>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."通过如下步骤，可以迅速地将指定的服务器映像部署到指定的计算资源上。";
	$disp = $disp."<br>";
	$disp = $disp."OpenQRM可以通过插件支持不同的存储和部署类型。因此，本快速入门文档所提供的仅仅是一个非常有限的概览。如果您需要更加详细的信息，请参考与您的配置所对应的<a href='howto.php'>实战教程</a>。";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<b>创建内核</b>";
	$disp = $disp."<br>";
	$disp = $disp."OpenQRM提供了名称为openqrm的客户端工具，用来创建新的内核。openqrm客户端工具的缺省安装位置为 /usr/share/openqrm/bin/openqrm 。";
	$disp = $disp."<br>";
	$disp = $disp."向OpenQRM添加一个内核：";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<i>openqrm kernel add -n [name] -v [version] -u [username] -p [password] -l [location] -i [initramfs/ext2]</i>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."从OpenQRM删除一个内核：";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<i>openqrm kernel remove -n [name] -u [username] -p [password]</i>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<b>创建（虚拟机）映像</b>";
	$disp = $disp."<br>";
	$disp = $disp."虚拟机映像通常是在部署阶段快速创建的。OpenQRM还通过存储插件提供了多种自动安装映像的方法（例如本地存储设备或者NFS设备）。";
	$disp = $disp."同时，以某种格式存储的虚拟机映像，能够迅速转换成以另外一种格式存储的虚拟机映像（使用tranform-to参数）。";
	$disp = $disp."这些选项可以通过映像部署参数来配置，具体的使用方法请参考各个插件的文档。";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	return $disp;
}




$output = array();
$output[] = array('label' => '快速入门', 'value' => documentation_quickstart());

?>
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
<link rel="stylesheet" type="text/css" href="documentation.css" />
<?php
echo htmlobject_tabmenu($output);
?>
