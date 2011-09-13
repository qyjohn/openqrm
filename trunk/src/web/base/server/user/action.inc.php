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
$strMsg = '';
$error = false;
$url = '';

switch ($_REQUEST['action']) {
	//--------------------------------------------------
	//  Update User
	//--------------------------------------------------
	case 'user_update':
		$user = new user(htmlobject_request('name'));

		if($user->check_user_exists() === true) {
			//--------------------------------------------------
			if(htmlobject_request('name') == '') {
				$strMsg .= '用户帐号不能为空。<br>';
				$error = true;
			} else {
				if(strstr($OPENQRM_USER->role, "administrator") || htmlobject_request('name') == $OPENQRM_USER->name) {
					$strCheck = $user->check_string_name(htmlobject_request('name'));
					if ($strCheck != '') {
						$strMsg .= '用户帐号必须是 '.$strCheck.'<br>';
						$error = true;
					}
				} else {
					$strMsg .= '用户帐号不能更改。<br>';
					$error = true;
				}
			}
			//--------------------------------------------------
			$user->get_role_name();
			if(!strstr($OPENQRM_USER->role, "administrator") && htmlobject_request('role') != $user->role['value'] && $error === false) {
				$strMsg .= '您没有修改用户角色的权限。<br>';
				$error = true;
			}
			//--------------------------------------------------
			if(htmlobject_request('password') != '') {
				$strCheck = $user->check_string_password(htmlobject_request('password'));
				if ($strCheck != '') {
					$strMsg .= '用户密码必须是 '.$strCheck.'<br>';
					$error = true;
				}
				if (htmlobject_request('password') != htmlobject_request('retype_password')) {
					$strMsg .= '两次输入的密码必须一致。<br>';
					$error = true;
				}
			}
		} else {
			$strMsg .= '没有找到用户帐号。<br>';
			$error = true;
		}
		//--------------------------------------------------
		if($error === false) {
			$user->set_user_from_request();
			$msg = $user->query_update();
			$strMsg .= '操作成功。 ';
			$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab='.$_REQUEST['currenttab'].'&name='.htmlobject_request('name');
		}
		break;
	//--------------------------------------------------
	//  Insert User
	//--------------------------------------------------
	case 'user_insert':
	$user = new user(htmlobject_request('name'));

		//--------------------------------------------------
		if(htmlobject_request('name') == '') {
			$strMsg .= '用户帐号不能为空。<br>';
			$error = true;
		} else {
			$strCheck = $user->check_string_name(htmlobject_request('name'));
			if ($strCheck != '') {
				$user->name = '';
				$strMsg .= '用户帐号必须是 '.$strCheck.'<br>';
				$error = true;
			}
		}
		if ($user->check_user_exists() === true) {
			$strMsg .= '该用户帐号已经被使用。<br>';
			$error = true;
		}
		//--------------------------------------------------
		if(htmlobject_request('password') == '') {
			$strMsg .= '用户密码不能为空。<br>';
			$error = true;
		} else {
			$strCheck = $user->check_string_password(htmlobject_request('password'));
			if ($strCheck != '') {
				$strMsg .= '用户密码必须是。 '.$strCheck.'<br>';
				$error = true;
			}
			if (htmlobject_request('password') != htmlobject_request('retype_password')) {
				$strMsg .= '两次输入的密码必须一致。<br>';
				$error = true;
			}
		}

		if($error === false) {
			$user->set_user_from_request();
			$msg = $user->query_insert();
			$strMsg .= '操作成功。 ';
		}
		break;
	//--------------------------------------------------
	//  Delete User 1
	//--------------------------------------------------
	case 'user_delete':
		if(strstr($OPENQRM_USER->role, "administrator") || htmlobject_request('name') == $OPENQRM_USER->name) {
			$url = $thisfile.'?delete=1&currenttab='.$_REQUEST['currenttab'].'&name='.htmlobject_request('name');
		} else {
			$strMsg .= '您没有删除用户的权限。<br>';
			$error = true;
		}
		break;
	//--------------------------------------------------
	//  Delete User 2
	//--------------------------------------------------
	case 'user_delete_2':
		if(strstr($OPENQRM_USER->role, "administrator") || htmlobject_request('name') == $OPENQRM_USER->name) {
			$user = new user(htmlobject_request('name'));
			$user->set_user();
			if($user->id != 0) {
				$user->query_delete();
				$strMsg .= '用户 <b>'.htmlobject_request('name').'</b> 已经被删除。<br>';
			} else {
				$strMsg .= '您没有删除ID为0的用户的权限。<br>';
				$error = true;
			}
		} else {
			$strMsg .= '您没有删除用户的权限。<br>';
			$error = true;
		}
		break;
}

if($error === true) {
	$strMsg = "<strong>错误：</strong><br>".$strMsg;
}
if($url == '') {
	$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab='.$_REQUEST['currenttab'];
}
header("Location: $url");
header("Method: Post");
exit;
?>
