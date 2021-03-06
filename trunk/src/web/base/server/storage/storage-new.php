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
    Copyright 2011, Qingye Jiang (John) <qjiang@ieee.org>
*/
$thisfile = basename($_SERVER['PHP_SELF']);
$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
$BaseDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/';
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/storage.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/deployment.class.php";
require_once "$RootDir/include/htmlobject.inc.php";

if(strtolower(OPENQRM_USER_ROLE_NAME) != 'administrator') {
	echo 'Access denied';
	exit;
}

function redirect($strMsg, $currenttab = 'tab0', $url = '') {
	global $thisfile;
	if($url == '') {
		$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab='.$currenttab;
	}
	header("Location: $url");
	exit;
}


if(htmlobject_request('action') != '') {
	$strMsg = '';
	$error = 0;

	switch (htmlobject_request('action')) {
		case 'save':
			// check passed values
			if(htmlobject_request('storage_name') != '') {
				if (!preg_match('#^[A-Za-z0-9_-]*$#', htmlobject_request('storage_name'))) {
					$strMsg .= 'Storage name must be [A-Za-z0-9_-]<br/>';
					$error = 1;
				}
				// check that name is unique
				$storage_name_check = new storage();
				$storage_name_check->get_instance_by_name(htmlobject_request('storage_name'));
				if ($storage_name_check->id > 0) {
					$strMsg .= "Storage name must be unique!<br/>";
					$error = 1;
				}

			} else {
				$strMsg .= "Storage name can not be empty<br/>";
				$error = 1;
			}
			if (htmlobject_request('identifier') == '') {
				$strMsg .= 'Please select a rescoure<br/>';
				$error = 1;
			}

			// if everything is fine
			if($error == 0) {

				$storage_fields = array();
				foreach ($_REQUEST as $key => $value) {
					if (strncmp($key, "storage_", 8) == 0) {
						$storage_fields[$key] = $value;
					}
				}
				if(isset($_REQUEST['identifier'])) {
					foreach($_REQUEST['identifier'] as $id) {
						$storage_fields["storage_resource_id"]=$id;
					}
				}
				$storage = new storage();
				$storage_fields["storage_id"]=openqrm_db_get_free_id('storage_id', $STORAGE_INFO_TABLE);
				$storage_type=htmlobject_request('storage_type');
				$storage_fields["storage_type"]="$storage_type";

				// unqote capabilities
				$storage_capabilities_parameter = $storage_fields["storage_capabilities"];
				$storage_fields["storage_capabilities"] = stripslashes($storage_capabilities_parameter);

				$storage->add($storage_fields);
				$strMsg .= 'added new storage <b>'.$storage_fields["storage_name"].'</b><br>';

				$args = '?strMsg='.$strMsg;
				$args .= '&storage_id='.$storage_fields["storage_id"];
				$args .= '&currentab=tab0';
				$url = 'storage-index.php'.$args;

			}
			// if something went wrong
			else {
				$url = error_redirect($strMsg);
			}
			redirect('', '', $url);
			break;
	}

}


$event = new event();


// we need to include the resource.class after the redirect to not send any header
require_once "$RootDir/class/resource.class.php";


function storage_edit() {
	global $thisfile;
	global $OPENQRM_USER, $BaseDir;

	$deployment = new deployment();
	$deployment_list = array();
	$deployment_list = $deployment->get_storagedescription_list();
	// remove the ramdisk-type from the list
	array_splice($deployment_list, 0, 1);

	if((htmlobject_request('action') == 'select' && isset($_REQUEST['identifier'])) || isset($_REQUEST['step'])) {

		$new_storage_step_2 = true;

		$deployment->get_instance_by_id(htmlobject_request('storage_type'));


		$store = '';
		$store .= htmlobject_input('storage_name', array("value" => htmlobject_request('storage_name'), "label" => 'Storage name'), 'text', 20);

		$helplink = '<a href="../../plugins/'.$deployment->storagetype.'/'.$deployment->storagetype.'-about.php" target="blank" class="doculink">
		'.$deployment->storagedescription.'
		</a>';

		$html = new htmlobject_div();
		$html->text = $helplink;
		$html->id = 'htmlobject_storage_type';

		$box = new htmlobject_box();
		$box->id = 'htmlobject_box_storage_type';
		$box->css = 'htmlobject_box';
		$box->label = 'Storage type';
		$box->content = $html;

		$store .= $box->get_string();
		$store .= htmlobject_input('storage_type', array("value" => $deployment->id, "label" => ''), 'hidden');

		// plugin the deployment-capabilities template values if existing
		$deployment_default_paramters="";
		$deployment_default_paramters_file = "$BaseDir/boot-service/storage.$deployment->storagetype";
		if (file_exists($deployment_default_paramters_file)) {
			$deployment_default_paramters = file_get_contents("$deployment_default_paramters_file");
		}
		$store .= htmlobject_textarea('storage_capabilities', array("value" => $deployment_default_paramters, "label" => 'Storage Capabilities'));
		$store .= htmlobject_textarea('storage_comment', array("value" => htmlobject_request('storage_comment'), "label" => 'Comment'));

		$store .= htmlobject_input('currenttab', array("value" => 'tab1', "label" => ''), 'hidden');
		$store .= htmlobject_input('step', array("value" => '2', "label" => ''), 'hidden');
		$store .= htmlobject_input('identifier[]', array("value" => $_REQUEST['identifier'][0], "label" => ''), 'hidden');
		$store .= htmlobject_input('storage_filter', array("value" => htmlobject_request('storage_filter'), "label" => ''), 'hidden');

		$store_action = array('save');

	}  else {
		$selected = array();
		if(htmlobject_request('storage_type') != '') {
			$selected = array(htmlobject_request('storage_type'));
		} elseif (htmlobject_request('storage_filter') != '') {
			$tmp = $deployment->get_id_by_storagetype(htmlobject_request('storage_filter'));
			$selected = array($tmp[0]['value']);
		}

		$store = "";
		$store .= htmlobject_select('storage_type', $deployment_list, 'Deployment', $selected);
		$store .= htmlobject_input('currenttab', array("value" => 'tab1', "label" => ''), 'hidden');
		$store .= htmlobject_input('storage_filter', array("value" => htmlobject_request('storage_filter'), "label" => ''), 'hidden');
		$store_action = array('select');

	}


	$resource_tmp = new resource();

	$table = new htmlobject_db_table('resource_id', '', 10);
	$table->add_headrow($store);

	$arHead = array();
	$arHead['resource_state'] = array();
	$arHead['resource_state']['title'] ='';
	$arHead['resource_state']['sortable'] = false;

	$arHead['resource_icon'] = array();
	$arHead['resource_icon']['title'] ='';
	$arHead['resource_icon']['sortable'] = false;

	$arHead['resource_id'] = array();
	$arHead['resource_id']['title'] ='ID';

	$arHead['resource_hostname'] = array();
	$arHead['resource_hostname']['title'] ='名称';

	$arHead['resource_mac'] = array();
	$arHead['resource_mac']['title'] ='Mac地址';

	$arHead['resource_ip'] = array();
	$arHead['resource_ip']['title'] ='IP地址';

	$arHead['resource_vtype'] = array();
	$arHead['resource_vtype']['title'] ='类型';

	$arBody = array();

	if(isset($new_storage_step_2)) {
		$resource = new resource();
		$resource->get_instance_by_id($_REQUEST['identifier'][0]);
		if ($resource->id == 0) {
			$resource_icon_default="/openqrm/base/img/logo.png";
		} else {
			$resource_icon_default="/openqrm/base/img/resource.png";
		}
		$state_icon="/openqrm/base/img/$resource->state.png";
		// idle ?
		if (("$resource->imageid" == "1") && ("$resource->state" == "active")) {
			$state_icon="/openqrm/base/img/idle.png";
		}
		if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/".$state_icon)) {
			$state_icon="/openqrm/base/img/unknown.png";
		}
		if ($resource->id == 0) {
			$resource_type_info="openQRM Server";
			$resource->mac = "x:x:x:x:x:x";
		} else {
			$virtualization = new virtualization();
			$virtualization->get_instance_by_id($resource->vtype);
			$resource_type_info=$virtualization->name." on Res. ".$resource->vhostid;
		}

		$arBody[] = array(
			'resource_state' => "<img src=$state_icon>",
			'resource_icon' => "<img width=24 height=24 src=$resource_icon_default>",
			'resource_id' => $resource->id,
			'resource_hostname' => $resource->hostname,
			'resource_mac' => $resource->mac,
			'resource_ip' => $resource->ip,
			'resource_vtype' => $resource_type_info,
		);

		$table->add_headrow('<h3>Resource</h3>');

	} else {
		$resource_array = $resource_tmp->display_overview($table->offset, $table->limit, $table->sort, $table->order);
		foreach ($resource_array as $index => $resource_db) {
			// prepare the values for the array
			$resource = new resource();
			$resource->get_instance_by_id($resource_db["resource_id"]);
			$mem_total = $resource_db['resource_memtotal'];
			$mem_used = $resource_db['resource_memused'];
			$mem = "$mem_used/$mem_total";
			$swap_total = $resource_db['resource_swaptotal'];
			$swap_used = $resource_db['resource_swapused'];
			$swap = "$swap_used/$swap_total";
			if ($resource->id == 0) {
				$resource_icon_default="/openqrm/base/img/logo.png";
			} else {
				$resource_icon_default="/openqrm/base/img/resource.png";
			}
			$state_icon="/openqrm/base/img/$resource->state.png";
			// idle ?
			if (("$resource->imageid" == "1") && ("$resource->state" == "active")) {
				$state_icon="/openqrm/base/img/idle.png";
			}
			if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/".$state_icon)) {
				$state_icon="/openqrm/base/img/unknown.png";
			}
			$ident_command = '';
			if ($resource->id == 0) {
				$resource_type_info="openQRM Server";
				$resource_mac = "x:x:x:x:x:x";
			} else {
				$virtualization = new virtualization();
				$virtualization->get_instance_by_id($resource->vtype);
				$resource_type_info=$virtualization->name." on Res. ".$resource->vhostid;
				$resource_mac=$resource_db["resource_mac"];
			}

			$arBody[] = array(
				'resource_state' => "<img src=$state_icon>",
				'resource_icon' => "<img width=24 height=24 src=$resource_icon_default>",
				'resource_id' => $resource_db["resource_id"],
				'resource_hostname' => $resource_db["resource_hostname"],
				'resource_mac' => $resource_mac,
				'resource_ip' => $resource_db["resource_ip"],
				'resource_vtype' => $resource_type_info,
			);

		}

		$table->add_headrow('<h3>Resource List</h3>');
	}

	$table->id = 'Tabelle';
	$table->css = 'htmlobject_table';
	$table->border = 1;
	$table->cellspacing = 0;
	$table->cellpadding = 3;
	$table->form_action = $thisfile;
	$table->head = $arHead;
	$table->body = $arBody;
	if ($OPENQRM_USER->role == "administrator") {

		$table->bottom = $store_action;
		$table->identifier = 'resource_id';
		$table->identifier_type = 'radio';
	}

	if(isset($new_storage_step_2)) {
		$table->sort = '';
		$table->identifier = '';
	}

	$all = $resource_tmp->get_count('all');
	$table->max = $all + 1; // add openqrmserver

	if (count($deployment_list) > 0) {
		return "<h1>创建存储</h1>".$table->get_string();
	} else {
		$str = '<h1>创建存储</h1>';
		$str .= '<center>';
		$str .= '<b>没有可用的存储插件</b>';
		$str .= '<br><br>';
		$str .= '<a href="../../plugins/aa_plugins/plugin-manager.php?plugin_filter=storage">管理插件</a>';
		$str .= '</center>';
		$str .= '<br><br>';
		return $str;
	}
}


$output = array();
$output[] = array('label' => '存储列表', 'value' => '', 'target' => 'storage-index.php', 'request' => array('storage_filter' => htmlobject_request('storage_filter')));
$output[] = array('label' => '创建存储', 'value' => storage_edit(), 'request' => array('storage_filter' => htmlobject_request('storage_filter')));


?>
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
<link rel="stylesheet" type="text/css" href="storage.css" />
<?php

$tabmenu = new htmlobject_tabmenu($output);
$tabmenu->css = 'htmlobject_tabs';



echo $tabmenu->get_string();
?>
