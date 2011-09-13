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
$thisfile = basename($_SERVER['PHP_SELF']);
$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
$BaseDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/';
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/storage.class.php";
require_once "$RootDir/class/deployment.class.php";
require_once "$RootDir/include/htmlobject.inc.php";

if(strtolower(OPENQRM_USER_ROLE_NAME) != 'administrator') {
	echo 'Access denied';
	exit;
}

$storage_id = htmlobject_request("storage_id");


function redirect($strMsg, $currenttab = 'tab0', $url = '') {
	global $thisfile;
	if($url == '') {
		$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab='.$currenttab.'&storage_filter='.htmlobject_request('storage_filter');
	}
	header("Location: $url");
	exit;
}

if(htmlobject_request('action') != '') {
	$strMsg = '';
	$error = 0;

	switch (htmlobject_request('action')) {

		case 'update':

			$storage_name = htmlobject_request('storage_name');

			// check passed values
			if($storage_name != '') {
				if (!preg_match('#^[A-Za-z0-9_-]*$#', $storage_name)) {
					$strMsg .= 'storage name must be [A-Za-z0-9_-]<br/>';
					$error = 1;
				}
			} else {
				$strMsg .= "storage name can not be empty<br/>";
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

				// unqote capabilities
				$storage_capabilities_parameter = $storage_fields["storage_capabilities"];
				$storage_fields["storage_capabilities"] = stripslashes($storage_capabilities_parameter);

				$storage = new storage();
				$storage->update($storage_id, $storage_fields);
				$strMsg .= 'updated storage <strong>'.$storage_fields["storage_name"].'</strong><br>';

				$args = '?strMsg='.$strMsg;
				$args .= '&storage_id='.$storage_fields["storage_id"];
				$args .= '&currenttab=tab0';
				$args .= '&storage_filter='.htmlobject_request('storage_filter');
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


function storage_edit($storage_id='') {

	global $OPENQRM_USER, $BaseDir;

	$deployment = new deployment();
	$deployment_list = array();
	$deployment_list = $deployment->get_list();
	// remove the ramdisk-type from the list
	array_splice($deployment_list, 0, 1);

	$storage = new storage();
	$storage->get_instance_by_id($storage_id);
	$storage_resource_id = $storage->resource_id;
	$deployment->get_instance_by_id($storage->type);

	$store = "<h1>Edit Storage</h1>";
	$store .= htmlobject_input('storage_name', array("value" => $storage->name, "label" => 'Storage name'), 'text', 20);

	$int = $storage->type;


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
	$store .= htmlobject_input('storage_type', array("value" => $storage->type, "label" => ''), 'hidden');

	$capabilities = htmlobject_request('storage_capabilities');
	if($capabilities == '') {
		$capabilities = $storage->capabilities;
	}
	$comment = htmlobject_request('storage_comment');
	if($comment == '') {
		$comment = $storage->comment;
	}
	$store .= htmlobject_textarea('storage_capabilities', array("value" => $capabilities, "label" => 'Storage Capabilities'));
	$store .= htmlobject_textarea('storage_comment', array("value" => $comment, "label" => 'Comment'));
	$store .= htmlobject_input('storage_id', array("value" => $storage_id, "label" => ''), 'hidden');
	$store .= htmlobject_input('currenttab', array("value" => 'tab2', "label" => ''), 'hidden');
	$store .= htmlobject_input('storage_filter', array("value" => htmlobject_request('storage_filter'), "label" => ''), 'hidden');

	$store_action = array('update');

	$resource_tmp = new resource();

	$table = new htmlobject_db_table('resource_id', '', 10);
	$table->add_headrow($store);
	$table->add_headrow('<h3>Resource List</h3>');

	$arHead = array();
	$arHead['resource_state'] = array();
	$arHead['resource_state']['title'] ='';
	$arHead['resource_state']['sortable'] = false;

	$arHead['resource_icon'] = array();
	$arHead['resource_icon']['title'] ='';
	$arHead['resource_icon']['sortable'] = false;

	$arHead['resource_id'] = array();
	$arHead['resource_id']['title'] ='编号';

	$arHead['resource_hostname'] = array();
	$arHead['resource_hostname']['title'] ='名称';

	$arHead['resource_mac'] = array();
	$arHead['resource_mac']['title'] ='Mac地址';

	$arHead['resource_ip'] = array();
	$arHead['resource_ip']['title'] ='IP地址';

	$arHead['resource_vtype'] = array();
	$arHead['resource_vtype']['title'] ='类型';

	$arBody = array();

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

	$table->id = 'Tabelle';
	$table->css = 'htmlobject_table';
	$table->border = 1;
	$table->cellspacing = 0;
	$table->cellpadding = 3;
	$table->form_action = "storage-edit.php";
	$table->head = $arHead;
	$table->body = $arBody;
	if ($OPENQRM_USER->role == "administrator") {
		$table->identifier_checked = array($storage_resource_id);
		$table->bottom = $store_action;
		$table->identifier = 'resource_id';
		$table->identifier_type = 'radio';
	}
	$all = $resource_tmp->get_count('all');
	$table->max = $all + 1; // add openqrmserver

	if (count($deployment_list) > 0) {
		return $table->get_string();
	} else {
		$str = '<center>';
		$str .= '<h1>没有可用的存储插件</h1>';
		$str .= '<br><br>';
		$str .= '<a href="../../plugins/aa_plugins/plugin-manager.php">管理插件</a>';
		$str .= '</center>';
		$str .= '<br><br>';
		return $str;
	}
}


$output = array();
$output[] = array('label' => '存储列表', 'value' => '', 'target' => 'storage-index.php', 'request' => array('storage_filter' => htmlobject_request('storage_filter')));
$output[] = array('label' => '创建存储', 'value' => '', 'target' => 'storage-new.php', 'request' => array('storage_filter' => htmlobject_request('storage_filter')));
$output[] = array('label' => '编辑存储', 'value' => storage_edit($storage_id), 'request' => array('storage_id' => $storage_id, 'storage_filter' => htmlobject_request('storage_filter')));


		

?>
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
<link rel="stylesheet" type="text/css" href="storage.css" />
<?php
$tabmenu = new htmlobject_tabmenu($output);
$tabmenu->css = 'htmlobject_tabs';
echo $tabmenu->get_string();
?>
