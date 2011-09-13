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

<form action="{formaction}" method="GET">
<h1>我的帐号</h1>

<div id="base">
	<div  id="account_edit">
		{cu_name_input}
		{cu_password_input}
		{cu_password_check_input}
		{cu_forename_input}
		{cu_lastname_input}
		{cu_email_input}
		{cu_street_input}
		{cu_city_input}
		{cu_country_input}
		{cu_phone_input}
		{cu_id}
		<div id="submit">
			{submit_save}
		</div>

		<div id="cloud_info">
			<b><u>云计算币</u></b>
			<br>
			<br>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>{cu_ccunits} CCU's</strong>
			<br>
			<br>
			<hr>
			<br>
			<b><u>全局设定</u></b>
			<br>
			<small>（由系统管理员设置）</small>
			<br>
			{cloud_global_limits}
			<br>
			<hr>
			<br>
			<b><u>用户设定</u></b>
			<br>
			<small>（0 = 无限制）</small>
			<br>
			{cloud_user_limits}
		</div>


	</div>


	<div  id="cloud_billing">
		<b><u>云服务收费记录（最近10个）</u></b>
		<br>
		<br>
		{cloud_transactions}
		<br>
	</div>

	<div id="cloud_transactions">
		<a href="mycloudtransactions.php" target="_BLANK"><small>所有记录</small></a>
	</div>

</div>

{currenttab}
</form>
