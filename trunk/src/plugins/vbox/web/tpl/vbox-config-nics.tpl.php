<!--
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
-->
<style>
.htmlobject_tab_box {
	width:700px;
}
</style>

<h1><img border=0 src="/openqrm/base/plugins/vbox/img/manager.png"> VirtualBox VM NET Configuration</h1>
{backlink}
<br>

{vm_config_nic_disp}
<br><hr><br>

<form action="{thisfile}" method="post">
{vm_config_nic1_disp}
<br>
<br><hr><br>

<form action="{thisfile}" method="post">
{vm_config_nic2_disp}
</form>
<br><hr><br>

<form action="{thisfile}" method="post">
{vm_config_nic3_disp}
</form>
<br><hr><br>

<form action="{thisfile}" method="post">
{vm_config_nic4_disp}
</form>
<br><hr><br>

<br><hr><br>

</form>


<form action="{thisfile}" method="post">
<div style="float:left;">
{vm_config_add_nic_disp}
</div>

<div style="float:right;">
    <div>
    <br>
    </div>
</div>
<div style="clear:both;line-height:0px;">&#160;</div>
{submit}
</form>

