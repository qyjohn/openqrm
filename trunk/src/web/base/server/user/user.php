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
error_reporting(E_ALL);
$thisfile = basename($_SERVER['PHP_SELF']);
require_once('../../include/htmlobject.inc.php');
require_once('../../include/user.inc.php');

if(htmlobject_request('action') != '') {
	require_once('action.inc.php');
}


if(htmlobject_request('name') != '' && strstr($OPENQRM_USER->role, "administrator")) {
	$user = new user(htmlobject_request('name'));
} else  {
	$user = new user($_SERVER['PHP_AUTH_USER']);
}


$user->set_user_form();

function html_elements() {
global $user;

$GLOBALS['html_id'] = htmlobject_input('id', $user->id, 'hidden', 5);
// $GLOBALS['html_name'] = htmlobject_input('name', $user->name, 'text', 20);
$GLOBALS['html_name'] = htmlobject_input('name', '帐号', 'text', 20);
$GLOBALS['html_password'] = htmlobject_input('password', array("label" => '设置密码', "value" => ''), 'password', 20);
$GLOBALS['html_retype_password'] = htmlobject_input('retype_password', array("label" => '重输密码', "value" => ''), 'password', 20);
$GLOBALS['html_gender'] = htmlobject_select('gender', $user->get_gender_list(), '性别', array($user->gender['value']));
$GLOBALS['html_first_name'] = htmlobject_input('first_name', array("label" => '名', "value" => $user->first_name['value']), 'text', 50);
$GLOBALS['html_last_name'] = htmlobject_input('last_name',  array("label" => '姓', "value" => $user->last_name['value']), 'text', 50);
$GLOBALS['html_department'] = htmlobject_input('department',  array("label" => '部门', "value" => $user->department['value']), 'text', 50);
$GLOBALS['html_office'] = htmlobject_input('office',   array("label" => '办公室', "value" => $user->office['value']), 'text', 50);
$GLOBALS['html_role'] = htmlobject_select('role', $user->get_role_list(), '角色', array($user->role['value']));
$GLOBALS['html_last_update_time'] = htmlobject_input('last_update_time',   array("label" => '更新', "value" => $user->last_update_time['value']), 'text', 50);
$GLOBALS['html_description'] = htmlobject_textarea('description',   array("label" => '用户描述', "value" => $user->description['value']));
$GLOBALS['html_capabilities'] = htmlobject_textarea('capabilities',   array("label" => '用户权限', "value" => $user->capabilities['value']));
$GLOBALS['html_state'] = htmlobject_input('state',  array("label" => '状态', "value" => $user->state['value']), 'text', 20);

}

// Delete User step 2
//---------------------------------------------------------
if(htmlobject_request('delete') == 1) {

$account_output = '
<form action="'.$thisfile.'" method="post">
<input type="hidden" name="currenttab" value="tab0">
<input type="hidden" name="action" value="user_delete_2">
<input type="hidden" name="name" value="'.htmlobject_request('name').'">
<center>
<br><br>
确认要删除用户 <strong>'.htmlobject_request('name').'</strong> 吗?
<br><br>
<br><br>
<input type="submit" value="ok" class="button">
<br><br>
</center>
</form>
';

} 
// Standard Output
//---------------------------------------------------------
else {

html_elements();

$switch = '
<table>
<tr>
<td><label for="action_up">更新</label></td>
<td><input type="radio" name="action" id="action_up" value="user_update" checked></td>
<td><label for="action_del">删除</label></td>
<td><input type="radio" name="action" id="action_del" value="user_delete"></td>
<td><input type="submit" class="button"></td>
</tr>
</table>
';

$account_output = "
<form action=\"$thisfile\" method=\"post\">
<input type=\"hidden\" name=\"currenttab\" value=\"tab0\">
$html_id
$html_name
$html_password
$html_retype_password
$html_role
$html_first_name
$html_last_name
$html_gender
$html_department
$html_office
$html_state
$html_last_update_time
$html_description
$html_capabilities
$switch

</form>
";

}

$output = array();
$output[] = array('label' => '用户帐号', 'value' => $account_output);

if (strstr($OPENQRM_USER->role, "administrator")) {

	//---------------------------------------------------------
	$ar_edit = array();
	$ar_users = $user->get_users();

	$i = 0;
	foreach ($ar_users as $ar) {
	$tmp = '';
	if($i == 0) {
		foreach ($ar as $val) {
			$tmp .= '<th class="th">'.$val.'</th>';
		}
		$ar_edit[] = '<tr>';
		$ar_edit[] = $tmp;
		$ar_edit[] = '</tr>';
	} else {
		foreach ($ar as $val) {
			$text = $val['value'];
			if($text == '') { $text = '&#160;'; }
			$tmp .= '<td class="'. $val['label'] .' td">'.$text.'</td>';
		}
		$ar_edit[] = '
			<tr class="tr"
				onmouseover="this.style.backgroundColor = \'#eeeeee\';"
				onmouseout="this.style.backgroundColor = \'transparent\'";
				onclick="location.href=\''.$thisfile.'?currenttab=tab0&name='.$ar[0]['value'].'\';">
		';
		$ar_edit[] = $tmp;
		$ar_edit[] = '</tr>';
	}
	$i++;	
	}

	$edit_user_output = "
	<form action=\"$thisfile\" method=\"post\">
	<input type=\"hidden\" name=\"currenttab\" value=\"tab1\">
	<input type=\"hidden\" name=\"action\" value=\"user_edit\">
	<table class=\"table\" cellspacing=\"0\">
	";
	
	foreach ( $ar_edit as $res ) {
		$edit_user_output .= ''.$res.'';
	}
	
	$edit_user_output .= "
	</table>
	</form>
	";
	$output[] = array('label' => '编辑用户', 'value' => $edit_user_output);

	// Restet user to recive empty form
	//---------------------------------------------------------
	$user->id['value'] = '';
	$user->name['value'] = '';
	$user->password['value'] = '';
	$user->gender['value'] = '';
	$user->role['value'] = '';
	$user->first_name['value'] = '';
	$user->last_name['value'] = '';
	$user->department['value'] = '';
	$user->office['value'] = '';
	$user->last_update_time['value'] = '';
	$user->description['value'] = '';
	$user->capabilities['value'] = '';
	$user->state['value'] = '';

	html_elements();

	$add_user_output = "
	<form action=\"$thisfile\" method=\"post\">
	<input type=\"hidden\" name=\"currenttab\" value=\"tab2\">
	<input type=\"hidden\" name=\"action\" value=\"user_insert\">
	$html_id
	$html_name
	$html_password
	$html_retype_password
	$html_role
	$html_first_name
	$html_last_name
	$html_gender
	$html_department
	$html_office
	$html_state
	$html_description
	$html_capabilities
	<input type=\"submit\" class=\"button\">
	</form>
	";
	$output[] = array('label' => '添加用户', 'value' => $add_user_output);
}

echo htmlobject_head('用户管理');

?>
<body>
<style>
.user_name { width:74px; }
.user_id { width:40px; }
.user_first_name { width:90px; }
.user_last_name { width:90px; }
.user_role { width:50px; }
.user_last_update_time { width:80px; }
</style>

<?php

// if ldap is enabled do not allow access the the openQRM user administration
if (file_exists("$RootDir/plugins/ldap/.running")) {
	unset($output);
	$output[] = array('label' => '禁用', 'value' => "本系统启用了LDAP身份认证。OpenQRM用户管理功能被禁用。");

}

echo htmlobject_tabmenu($output);


?>

</body>
</html>



