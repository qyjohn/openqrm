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
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/include/htmlobject.inc.php";
global $OPENQRM_WEB_PROTOCOL;


function documentation_installation() {
	global $OPENQRM_WEB_PROTOCOL;
	$disp = "<h1>安装配置</h1>";
	$disp = $disp."<br>";
	$disp = $disp."不管是采用编译源代码还是通过安装二进制包的方式，OpenQRM的安装配置都非常简单。";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<b>通过源代码安装OpenQRM：</b>";


	$disp = $disp."<ul>";
	$disp = $disp."<li>";
	$disp = $disp."从sourceforge.net的OpenQRM SVN代码库获得最新版本的源代码：";
	$disp = $disp."<br>";
	$disp = $disp."<i>svn co https://openqrm.svn.sourceforge.net/svnroot/openqrm openqrm</i>";
	$disp = $disp."</li><li>";
	$disp = $disp."进入源代码目录：";
	$disp = $disp."<br>";
	$disp = $disp."<i>cd openqrm/trunk/src</i>";
	$disp = $disp."</li><li>";
	$disp = $disp."运行 <b>make</b>";
	$disp = $disp."<br>";
	$disp = $disp."<i>make</i>";
	$disp = $disp."</li><li>";
	$disp = $disp."运行 <b>make install</b>";
	$disp = $disp."<br>";
	$disp = $disp."（需要root权限）";
	$disp = $disp."<br>";
	$disp = $disp."<i>make install</i>";
	$disp = $disp."</li><li>";
	$disp = $disp."运行 <b>make start</b>";
	$disp = $disp."<br>";
	$disp = $disp."（需要root权限）";
	$disp = $disp."<br>";
	$disp = $disp."<i>make start</i>";
	$disp = $disp."</li>";
	$disp = $disp."</ul>";
	$disp = $disp."<br>";
	$disp = $disp."在安装过程中遇到的软件依赖性问题，会由OpenQRM安装程序自动解决。";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<b>通过二进制包安装OpenQRM：</b>";
	$disp = $disp."<br>";
	$disp = $disp."首先，您需要从sourceforge.net的项目下载页面下载您所需要安装的软件包。";
	$disp = $disp."可供选择的OpenQRM软件包包括：";
	$disp = $disp."<ul>";
	$disp = $disp."<li>";
	$disp = $disp."<b>openqrm-server-entire</b>";
	$disp = $disp."<br>";
	$disp = $disp."包含OpenQRM服务器以及所有的插件";
	$disp = $disp."</li><li>";
	$disp = $disp."<b>openqrm-server</b>";
	$disp = $disp."<br>";
	$disp = $disp."饱含OpenQRM服务器但没有任何插件";
	$disp = $disp."</li><li>";
	$disp = $disp."<b>openqrm-plugin-[plugin-name]</b>";
	$disp = $disp."<br>";
	$disp = $disp."每个插件都是一个独立的软件包";
	$disp = $disp."</li>";
	$disp = $disp."</ul>";
	$disp = $disp."<br>";
	$disp = $disp."然后，使用与您所使用的Linux发行版所对应的包管理工具安装您所下载的软件包。";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<b>安装成功后的系统配置：</b>";
	$disp = $disp."<br>";
	$disp = $disp."安装成功之后，通过浏览器访问OpenQRM服务器：";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<b>$OPENQRM_WEB_PROTOCOL://[ip-address]/openqrm</b>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."其中，[ip-address] 是您所安装OpenQRM服务的服务器IP地址。";
	$disp = $disp."<br>";
	$disp = $disp."初始的用户名是openqrm，密码也是openqrm。";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."在第一次登录之后，请务必修改您的密码。";
	$disp = $disp."<br>";
	return $disp;
}




$output = array();
$output[] = array('label' => '安装配置', 'value' => documentation_installation());

?>
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
<link rel="stylesheet" type="text/css" href="documentation.css" />
<?php
echo htmlobject_tabmenu($output);
?>
