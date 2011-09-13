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
	width:600px;
	height:500px;
}

.htmlobject_table {
	width:250px;
}


#config_text {
	position: absolute;
	left: 40px;
	width:260px;
	top: 200px;
	padding: 10px;
	border: solid 1px #ccc;
}

#config_table {
	position: absolute;
	left: 340px;
	width:280px;
	top: 200px;
	padding: 10px;
	border: solid 1px #ccc;
}

#steps {
	position: absolute;
	left: 530px;
	width:350px;
	top: 50px;
}


#openqrm_logo {
	position: absolute;
	left: 130px;
	width:150px;
	top: 410px;
	padding: 10px;
}

a {
    text-decoration:none
}

</style>
<div>
	<h1>CloudPro配置向导</h1>
	<div id="steps">
	<a href="/openqrm">第一步</a> - <a href="/openqrm/base/configure.php?step=2">第二步</a> - <strong>第三步</strong>
	</div>

	<div id="config_text">
	<h4>配置数据库连接</h4>
	请填写连接数据库所必要的参数，包括数据库服务器、数据库名、用户名、密码。
	</div>

	<div id="config_table">
		{db_config_table}
	</div>

	<div id="openqrm_logo">
		<a href="http://www.openqrm.com" target="_BLANK">
		&nbsp;&nbsp;&nbsp;<img src="/openqrm/base/img/logo.png" width="100" height="48" border="0" alt="Your open-source Cloud computing platform"/>
		<br>
		CloudPro 4.8
		</a>
	</div>

</div>

