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
    Copyright 2011, Qingye Jiang (John) <qjiang@ieee.org>
*/
-->
<style>
.htmlobject_tab_box {
	width:600px;
}
</style>
<form action="{thisfile}">

{new_image_step_2}
{identifier}
{currentab}

<div>
	<div style="float:left;">
	{image_type}
	{image_name}
	{image_version}
	{image_passwd}
	{image_rootdevice}
	{image_rootfstype}
	</div>
	<div style="float:right;">
		<div style="border: solid 1px #ccc; padding: 10px 10px 0 10px;">
		{storage_type}
		{image_deployment}
		{storage_resource_id}
		</div>
	</div>
	<div style="clear:both;line-height:0px;">&#160;</div>

	<div style="float:left;">
	{install_from_nfs}
	{transfer_to_nfs}
	</div>
	<div style="float:right;">
	{install_from_local}
	{transfer_to_local}
	</div>

	<div style="clear:both;line-height:0px;">&#160;</div>
</div>

{image_deployment_parameter}
{image_deployment_comment}
{image_capabilities}

<div style="text-align:right;">{submit_save}</div>

</form>