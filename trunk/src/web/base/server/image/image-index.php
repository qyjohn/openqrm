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
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/storage.class.php";
require_once "$RootDir/class/deployment.class.php";
require_once "$RootDir/include/htmlobject.inc.php";


function redirect($strMsg, $currenttab = 'tab0', $url = '') {
	global $thisfile;
	if($url == '') {
		$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab='.$currenttab;
	}
	header("Location: $url");
	exit;
}



if(htmlobject_request('action') != '' && strtolower(OPENQRM_USER_ROLE_NAME) == 'administrator') {
	$strMsg = '';

	switch (htmlobject_request('action')) {
		case 'remove':
			switch (htmlobject_request('subaction')) {
				case '' :
					if(isset($_REQUEST['id'])) {
						$i = 0;
						$str_ident = '';
						$args = array('action' => 'remove');
						$checked = array();
						$arBody = array();
						$image = new image();

						foreach($_REQUEST['id'] as $id) {
							$image->get_instance_by_id($id);
							#$deployment->get_instance_by_id($image->type);
							$arBody[$i] = array(
								'image_id' => $image->id,
								'image_name' => $image->name,
								'image_type' => $image->comment,
							);
							$str_ident .= htmlobject_input('identifier[]', array('value' => $id, 'label' => ''), 'hidden');
							$args =  array_merge($args, array('id[]' => $id));
							$checked[] = $id;
							$i++;
						}
						$arHead = array();
						$arHead['image_id'] = array();
						$arHead['image_id']['title'] ='编号';
						$arHead['image_name'] = array();
						$arHead['image_name']['title'] ='名称';
						$arHead['image_type'] = array();
						$arHead['image_type']['title'] ='类别';

						$table = new htmlobject_table_builder('','','','','','del_');
						$table->add_headrow('<a href="'.$thisfile.'"><< cancel</a>'.htmlobject_input('action', array('value' => 'remove', 'label' => ''), 'hidden').$str_ident);
						$table->id = 'Tabelle';
						$table->css = 'htmlobject_table';
						$table->border = 1;
						$table->cellspacing = 0;
						$table->cellpadding = 3;
						$table->form_action = $thisfile;
						$table->head = $arHead;
						$table->body = $arBody;
						$table->bottom_buttons_name = 'subaction';
						$table->bottom = array('remove');
						$table->identifier_checked = $checked;
						$table->identifier_name = 'delident';
						$table->identifier = 'image_id';
						$table->max = count($arBody);

						$arAction = array("label" => 'Remove image', "value" => $table->get_string(), "request" => $args); // change tabs
					}
				break;
				//-----------------------------------------------------------
				case 'remove' :
					$image = new image();
					if(isset($_REQUEST['delident'])) {
						foreach($_REQUEST['delident'] as $id) {
							// check that image is not still used by an appliance
							$image_is_used_by_appliance = "";
							$remove_error = 0;
							$appliance = new appliance();
							$appliance_id_list = $appliance->get_all_ids();
							foreach($appliance_id_list as $appliance_list) {
								$appliance_id = $appliance_list['appliance_id'];
								$app_image_remove_check = new appliance();
								$app_image_remove_check->get_instance_by_id($appliance_id);
								if ($app_image_remove_check->imageid == $id) {
									$image_is_used_by_appliance .= $appliance_id." ";
									$remove_error = 1;
								}
							}
							if ($remove_error == 1) {
								$strMsg .= "Image id ".$id." is used by appliance(s): ".$image_is_used_by_appliance." <br>";
								$strMsg .= "Not removing image id ".$id." !<br>";
								continue;
							}
							// here we remove the image
							$image->remove($id);
							$strMsg .= "Removed image id ".$id."<br>";
						}
						redirect($strMsg);
					}
				break;
			}
		break;
	}
}


// we need to include the resource.class after the redirect to not send any header
require_once "$RootDir/class/resource.class.php";


function image_display() {
	global $OPENQRM_USER;
	global $thisfile;

	$image_tmp = new image();
	$table = new htmlobject_db_table('image_id');

	$disp = '<h1>映像列表</h1>';
	$disp .= '<br>';

	$arHead = array();
	$arHead['image_icon'] = array();
	$arHead['image_icon']['title'] ='';
	$arHead['image_icon']['sortable'] = false;

	$arHead['image_id'] = array();
	$arHead['image_id']['title'] ='编号';

	$arHead['image_name'] = array();
	$arHead['image_name']['title'] ='名称';

	$arHead['image_version'] = array();
	$arHead['image_version']['title'] ='版本';

	$arHead['image_type'] = array();
	$arHead['image_type']['title'] ='类别';

	$arHead['image_comment'] = array();
	$arHead['image_comment']['title'] ='说明';
	$arHead['image_comment']['sortable'] = false;

	$arHead['image_edit'] = array();
	$arHead['image_edit']['title'] ='';
	$arHead['image_edit']['sortable'] = false;
	if(strtolower(OPENQRM_USER_ROLE_NAME) != 'administrator') {
		$arHead['image_edit']['hidden'] = true;
	}

	$arBody = array();
	$image_array = $image_tmp->display_overview($table->offset, $table->limit, $table->sort, $table->order);
	$image_icon = "/openqrm/base/img/image.png";

	foreach ($image_array as $index => $image_db) {
		$image = new image();
		$image->get_instance_by_id($image_db["image_id"]);
		$image_deployment = new deployment();
		$image_deployment->get_instance_by_type($image_db["image_type"]);

		$strEdit = '';
		if($image_db["image_id"] != 1) {
			$strEdit = '<a href="image-edit.php?image_id='.$image_db["image_id"].'&currenttab=tab2"><img src="../../img/edit.png" width="24" height="24" alt="edit"/> Edit</a>';
		}

		$arBody[] = array(
			'image_icon' => "<img width=20 height=20 src=$image_icon>",
			'image_id' => $image_db["image_id"],
			'image_name' => $image_db["image_name"],
			'image_version' => $image_db["image_version"],
			'image_type' => $image_deployment->description,
			'image_comment' => $image_db["image_comment"],
			'image_edit' => $strEdit,
		);

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
		$table->bottom = array('remove');
		$table->identifier = 'image_id';
		$table->identifier_name = 'id';
		$table->identifier_disabled = array(1);
	}
		// do not show the openQRM server and idle image
		$image_max = $image_tmp->get_count();
	$table->max = $image_max-2;
	#$table->limit = 10;

	return $disp.$table->get_string();
}


$ar_tabs = array();
if(isset($arAction)) {
	$ar_tabs[] = $arAction;
} else {
	$ar_tabs[] = array('label' => '映像列表', 'value' => image_display());
	if(strtolower(OPENQRM_USER_ROLE_NAME) == 'administrator') {
		$ar_tabs[] = array('label' => '创建映像', 'target' => 'image-new.php');
	}
}


?>
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
<link rel="stylesheet" type="text/css" href="image.css" />
<?php
echo htmlobject_tabmenu($ar_tabs);
?>
