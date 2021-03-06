
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

function linux_vserver_about() {
	global $OPENQRM_SERVER_BASE_DIR;
	$disp = "<h1><img border=0 src=\"/openqrm/base/plugins/linux-vserver/img/plugin.png\"> Linux-VServer plugin</h1>";
	$disp = $disp."<br>";
	$disp = $disp."The linux-vserver-plugin ";
	$disp = $disp."Linux-VServer is known to be a great choice for applications which require a light-virtualization technology with very less overhead.";
	$disp = $disp."	Linux-VServer Virtualization hosts can be easily provisioned via openQRM by enabling this plugin. It also enables the administrator";
	$disp = $disp." to create, start, stop and deploy the 'vms' seamlessly through the web-interface. The virtual Linux-VServer-resources (vms) are then";
	$disp = $disp." transparently managed by openQRM in the same way as physical systems.";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<b>How to use :</b>";
	$disp = $disp."<br>";

	$disp = $disp."<ul>";
	$disp = $disp."<li>";
	$disp = $disp."Create an appliance and set its resource-type to 'Linux-VSever Host'";
	$disp = $disp."</li><li>";
	$disp = $disp."Use the 'VM Manager' in the Linux-VServer menu to create a new Linux-Vserver virtual-machines on the Host";
	$disp = $disp."</li><li>";
	$disp = $disp." The created Linux-VServer vm is then booting into openQRM as regular resources";
	$disp = $disp."</li>";
	$disp = $disp."</ul>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	return $disp;
}



$output = array();
$output[] = array('label' => 'About', 'value' => linux_vserver_about());
echo htmlobject_tabmenu($output);

?>


