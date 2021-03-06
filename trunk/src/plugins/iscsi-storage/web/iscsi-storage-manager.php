<!doctype html>
<html lang="en">
<head>
	<title>iSCSI Storage manager</title>
	<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
	<link rel="stylesheet" type="text/css" href="iscsi-storage.css" />
	<link type="text/css" href="/openqrm/base/js/jquery/development-bundle/themes/smoothness/ui.all.css" rel="stylesheet" />
	<script type="text/javascript" src="/openqrm/base/js/jquery/js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="/openqrm/base/js/jquery/js/jquery-ui-1.7.1.custom.min.js"></script>

<style type="text/css">

.ui-progressbar-value {
	background-image: url(/openqrm/base/img/progress.gif);
}

#progressbar {
	position: absolute;
	left: 150px;
	top: 250px;
	width: 400px;
	height: 20px;
}
</style>
</head>
<body>
<div id="progressbar">
</div>



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
$thisfile = basename($_SERVER['PHP_SELF']);
$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
$BaseDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/';
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/storage.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/deployment.class.php";
require_once "$RootDir/include/htmlobject.inc.php";

$action=htmlobject_request('action');
$iscsi_storage_id = htmlobject_request('iscsi_storage_id');
$iscsi_storage_name = htmlobject_request('iscsi_storage_id');
$iscsi_lun_size = htmlobject_request('iscsi_lun_size');
$iscsi_lun_name = htmlobject_request('iscsi_lun_name');
$iscsi_lun_snap_name = htmlobject_request('iscsi_lun_snap_name');
$iscsi_storage_fields = array();
foreach ($_REQUEST as $key => $value) {
	if (strncmp($key, "iscsi_storage_", 13) == 0) {
		$iscsi_storage_fields[$key] = $value;
	}
}
global $iscsi_storage_id;
global $iscsi_storage_name;
global $iscsi_lun_size;
global $iscsi_lun_name;
global $iscsi_lun_snap_name;

$refresh_delay=1;
$refresh_loop_max=20;


function redirect_iscsi($strMsg, $iscsi_storage_id) {
	global $thisfile;
	global $action;
	$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab=tab0&redirect=yes&action='.$action.'&identifier[]='.$iscsi_storage_id;
	echo "<meta http-equiv=\"refresh\" content=\"0; URL=$url\">";
	exit;
}

function redirect_iscsi_mgmt($strMsg, $iscsi_storage_id) {
	global $thisfile;
	global $action;
	$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab=tab0&redirect=yes&action='.$action.'&iscsi_storage_id='.$iscsi_storage_id;
	echo "<meta http-equiv=\"refresh\" content=\"0; URL=$url\">";
	exit;
}


function wait_for_statfile($sfile) {
	global $refresh_delay;
	global $refresh_loop_max;
	$refresh_loop=0;
	while (!file_exists($sfile)) {
		sleep($refresh_delay);
		$refresh_loop++;
		flush();
		if ($refresh_loop > $refresh_loop_max)  {
			return false;
		}
	}
	return true;
}


function show_progressbar() {
?>
	<script type="text/javascript">
		$("#progressbar").progressbar({
			value: 100
		});
		var options = {};
		$("#progressbar").effect("shake",options,2000,null);
	</script>
<?php
		flush();
}

function validate_input($var, $type) {
	switch ($type) {
		case 'string':
			// remove allowed chars
			$var = str_replace(".", "", $var);
			$var = str_replace("-", "", $var);
			$var = str_replace("_", "", $var);
			for ($i = 0; $i<strlen($var); $i++) {
				if (!ctype_alpha($var[$i])) {
					if (!ctype_digit($var[$i])) {
						return false;
					}
				}
			}
			return true;
			break;
		case 'number';
			for ($i = 0; $i<strlen($var); $i++) {
				if (!ctype_digit($var[$i])) {
					return false;
				}
			}
			return true;
			break;
	}
}

// running the actions
$redir_msg = '';
if(htmlobject_request('redirect') != 'yes') {
	if(htmlobject_request('action') != '') {
		if ($OPENQRM_USER->role == "administrator") {

			switch (htmlobject_request('action')) {
				case 'select':
					if (isset($_REQUEST['identifier'])) {
						foreach($_REQUEST['identifier'] as $id) {
							show_progressbar();
							$storage = new storage();
							$storage->get_instance_by_id($id);
							$storage_resource = new resource();
							$storage_resource->get_instance_by_id($storage->resource_id);
							$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/iscsi-storage/bin/openqrm-iscsi-storage post_luns -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
							// remove current stat file
							$storage_resource_id = $storage_resource->id;
							$statfile="storage/".$storage_resource_id.".iscsi.stat";
							if (file_exists($statfile)) {
								unlink($statfile);
							}
							// send command
							$storage_resource->send_command($storage_resource->ip, $resource_command);
							// and wait for the resulting statfile
							if (!wait_for_statfile($statfile)) {
								$redir_msg = "Error during refreshing iSCSI volumes ! Please check the Event-Log";
							} else {
								$redir_msg = "Displaying iSCSI volumes on storage id $id";
							}
							redirect_iscsi($redir_msg, $id);
						}
					}
					break;

				case 'reload':
					if (strlen($iscsi_storage_id)) {
						show_progressbar();
						$storage = new storage();
						$storage->get_instance_by_id($iscsi_storage_id);
						$storage_resource = new resource();
						$storage_resource->get_instance_by_id($storage->resource_id);
						$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/iscsi-storage/bin/openqrm-iscsi-storage post_luns -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
						// remove current stat file
						$storage_resource_id = $storage_resource->id;
						$statfile="storage/".$storage_resource_id.".iscsi.stat";
						if (file_exists($statfile)) {
							unlink($statfile);
						}
						// send command
						$storage_resource->send_command($storage_resource->ip, $resource_command);
						// and wait for the resulting statfile
						if (!wait_for_statfile($statfile)) {
							$redir_msg = "Error during refreshing iSCSI volumes ! Please check the Event-Log";
						} else {
							$redir_msg = "Displaying iSCSI volumes on storage id $iscsi_storage_id";
						}
						redirect_iscsi($redir_msg, $iscsi_storage_id);
					}
					break;


				case 'add':
					if (strlen($iscsi_storage_id)) {
						show_progressbar();
						if (!strlen($iscsi_lun_name)) {
							$redir_msg = "Got emtpy iSCSI volume name. Not adding ...";
							redirect_iscsi_mgmt($redir_msg, $iscsi_storage_id);
							exit(0);
						} else if (!validate_input($iscsi_lun_name, 'string')) {
							$redir_msg = "Got invalid iSCSI volume name. Not adding ...<br>(allowed characters are [a-z][A-z][0-9].-_)";
							redirect_iscsi_mgmt($redir_msg, $iscsi_storage_id);
							exit(0);
						}
						if (!strlen($iscsi_lun_size)) {
							$redir_msg = "Got emtpy iSCSI volume size. Not adding ...";
							redirect_iscsi_mgmt($redir_msg, $iscsi_storage_id);
							exit(0);
						} else if (!validate_input($iscsi_lun_size, 'number')) {
							$redir_msg = "Got invalid iSCSI volume size. Not adding ...";
							redirect_iscsi_mgmt($redir_msg, $iscsi_storage_id);
							exit(0);
						}
						$storage = new storage();
						$storage->get_instance_by_id($iscsi_storage_id);
						$storage_resource = new resource();
						$storage_resource->get_instance_by_id($storage->resource_id);
						// generate a password for the image
						$image = new image();
						$image_password = $image->generatePassword(12);
						$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/iscsi-storage/bin/openqrm-iscsi-storage add -n $iscsi_lun_name -m $iscsi_lun_size -i $image_password -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
						// remove current stat file
						$storage_resource_id = $storage_resource->id;
						$statfile="storage/".$storage_resource_id.".iscsi.stat";
						if (file_exists($statfile)) {
							unlink($statfile);
						}
						// send command
						$storage_resource->send_command($storage_resource->ip, $resource_command);
						// and wait for the resulting statfile
						if (!wait_for_statfile($statfile)) {
							$redir_msg .= "Error during adding iSCSI volume $iscsi_lun_name ! Please check the Event-Log<br>";
						} else {
							$redir_msg .= "Added iSCSI volume $iscsi_lun_name to storage id $iscsi_storage_id<br>";
						}
						redirect_iscsi_mgmt($redir_msg, $iscsi_storage_id);
					}
					break;

				case 'remove':
					if (strlen($iscsi_storage_id)) {
						show_progressbar();
						if (isset($_REQUEST['identifier'])) {
							$storage = new storage();
							$storage->get_instance_by_id($iscsi_storage_id);
							$storage_resource = new resource();
							$storage_resource->get_instance_by_id($storage->resource_id);
							// remove current stat file
							$storage_resource_id = $storage_resource->id;
							$statfile="storage/".$storage_resource_id.".iscsi.stat";
							if (file_exists($statfile)) {
								unlink($statfile);
							}
							// send command
							foreach($_REQUEST['identifier'] as $iscsi_lun_name) {
								$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/iscsi-storage/bin/openqrm-iscsi-storage remove -n $iscsi_lun_name -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
								$storage_resource->send_command($storage_resource->ip, $resource_command);
								$redir_msg .= "Removed iSCSI volume $iscsi_lun_name from storage id $iscsi_storage_id<br>";
								sleep(2);
							}
							// and wait for the resulting statfile
							if (!wait_for_statfile($statfile)) {
								$redir_msg = "Error during removing iSCSI volume ! Please check the Event-Log<br>";
							}
							redirect_iscsi_mgmt($redir_msg, $iscsi_storage_id);
						} else {
							$redir_msg = "No iSCSI volume selected. Skipping removal !";
							redirect_iscsi_mgmt($redir_msg, $iscsi_storage_id);
						}
					}
					break;

				case 'snap':
					if (strlen($iscsi_storage_id)) {
						show_progressbar();
						if (!strlen($iscsi_lun_name)) {
							$redir_msg = "Got emtpy iSCSI volume name. Not adding ...";
							redirect_iscsi_mgmt($redir_msg, $iscsi_storage_id);
							exit(0);
						} else if (!validate_input($iscsi_lun_name, 'string')) {
							$redir_msg = "Got invalid iSCSI volume name. Not adding ...<br>(allowed characters are [a-z][A-z][0-9].-_)";
							redirect_iscsi_mgmt($redir_msg, $iscsi_storage_id);
							exit(0);
						}
						if (!strlen($iscsi_lun_snap_name)) {
							$redir_msg = "Got emtpy iSCSI volume snapshot name. Not adding ...";
							redirect_iscsi_mgmt($redir_msg, $iscsi_storage_id);
							exit(0);
						} else if (!validate_input($iscsi_lun_snap_name, 'string')) {
							$redir_msg = "Got invalid iSCSI clone volume name. Not adding ...<br>(allowed characters are [a-z][A-z][0-9].-_)";
							redirect_iscsi_mgmt($redir_msg, $iscsi_storage_id);
							exit(0);
						}
						$storage = new storage();
						$storage->get_instance_by_id($iscsi_storage_id);
						$storage_resource = new resource();
						$storage_resource->get_instance_by_id($storage->resource_id);
						// remove current stat file
						$storage_resource_id = $storage_resource->id;
						$statfile="storage/".$storage_resource_id.".iscsi.stat";
						if (file_exists($statfile)) {
							unlink($statfile);
						}
						// send command
						$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/iscsi-storage/bin/openqrm-iscsi-storage snap -n $iscsi_lun_name -s $iscsi_lun_snap_name -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
						$storage_resource->send_command($storage_resource->ip, $resource_command);
						// and wait for the resulting statfile
						if (!wait_for_statfile($statfile)) {
							$redir_msg .= "Error during snapshotting iSCSI volume $iscsi_lun_name ! Please check the Event-Log<br>";
						} else {
							$redir_msg .= "Cloned iSCSI volume $iscsi_lun_name on storage id $iscsi_storage_id<br>";
						}
						redirect_iscsi_mgmt($redir_msg, $iscsi_storage_id);
					}
					break;

			}
		}
	}
}


function iscsi_select_storage() {
	global $OPENQRM_USER;
	global $thisfile;
	$table = new htmlobject_table_builder('storage_id', '', '', '', 'select');

	$arHead = array();
	$arHead['storage_state'] = array();
	$arHead['storage_state']['title'] ='';
	$arHead['storage_state']['sortable'] = false;

	$arHead['storage_icon'] = array();
	$arHead['storage_icon']['title'] ='';
	$arHead['storage_icon']['sortable'] = false;

	$arHead['storage_id'] = array();
	$arHead['storage_id']['title'] ='ID';

	$arHead['storage_name'] = array();
	$arHead['storage_name']['title'] ='Name';

	$arHead['storage_resource_id'] = array();
	$arHead['storage_resource_id']['title'] ='Res.ID';
	$arHead['storage_resource_id']['sortable'] = false;

	$arHead['storage_resource_ip'] = array();
	$arHead['storage_resource_ip']['title'] ='Ip';
	$arHead['storage_resource_ip']['sortable'] = false;

	$arHead['storage_type'] = array();
	$arHead['storage_type']['title'] ='Type';

	$arHead['storage_comment'] = array();
	$arHead['storage_comment']['title'] ='Comment';

	$storage_count=0;
	$arBody = array();
	$t_deployment = new deployment();
	$t_deployment->get_instance_by_type("iscsi-deployment");
	$storage_tmp = new storage();
	$storage_array = $storage_tmp->display_overview_per_type($t_deployment->id, $table->offset, $table->limit, $table->sort, $table->order);
	foreach ($storage_array as $index => $storage_db) {
		$storage = new storage();
		$storage->get_instance_by_id($storage_db["storage_id"]);
		$storage_resource = new resource();
		$storage_resource->get_instance_by_id($storage->resource_id);
		$deployment = new deployment();
		$deployment->get_instance_by_id($storage->type);
		$storage_count++;
		$resource_icon_default="/openqrm/base/img/resource.png";
		$storage_icon="/openqrm/base/plugins/iscsi-storage/img/storage.png";
		$state_icon="/openqrm/base/img/$storage_resource->state.png";
		if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/".$state_icon)) {
			$state_icon="/openqrm/base/img/unknown.png";
		}
		if (file_exists($_SERVER["DOCUMENT_ROOT"]."/".$storage_icon)) {
			$resource_icon_default=$storage_icon;
		}
		$arBody[] = array(
			'storage_state' => "<img src=$state_icon>",
			'storage_icon' => "<img width=24 height=24 src=$resource_icon_default>",
			'storage_id' => $storage->id,
			'storage_name' => $storage->name,
			'storage_resource_id' => $storage->resource_id,
			'storage_resource_ip' => $storage_resource->ip,
			'storage_type' => "$deployment->storagedescription",
			'storage_comment' => $storage->comment,
		);
	}

	$table->id = 'Tabelle';
	$table->css = 'htmlobject_table';
	$table->border = 1;
	$table->cellspacing = 0;
	$table->cellpadding = 3;
	$table->form_action = $thisfile;
	$table->identifier_type = "radio";
	$table->head = $arHead;
	$table->body = $arBody;
	if ($OPENQRM_USER->role == "administrator") {
		$table->bottom = array('select');
		$table->identifier = 'storage_id';
	}
	$table->max = $storage_tmp->get_count_per_type($t_deployment->id);

	// are there any storage server yet ?
	if(count($arBody) > 0) {
		$disp = $table->get_string();
	} else {
		$box = new htmlobject_box();
		$box->id = 'htmlobject_box_add_storage';
		$box->css = 'htmlobject_box';
		$box->label = '<br><nobr><b>No storage configured yet!</b></nobr>';
		$box_content = '<br><br><br><br>Please create an '.$t_deployment->storagedescription.' first!<br>';
		$box_content .= '<a href="/openqrm/base/server/storage/storage-new.php?currenttab=tab1"><b>New storage</b></a><br>';
		$box->content = $box_content;
		$disp = $box->get_string();
	}

   // set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './tpl/' . 'iscsi-storage-select.tpl.php');
	$t->setVar(array(
		'formaction' => $thisfile,
		'storage_server_table' => $disp,
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;
}



function iscsi_storage_display($iscsi_storage_id) {

	global $OPENQRM_USER;
	global $thisfile;
	$storage = new storage();
	$storage->get_instance_by_id($iscsi_storage_id);
	$storage_resource = new resource();
	$storage_resource->get_instance_by_id($storage->resource_id);
	$deployment = new deployment();
	$deployment->get_instance_by_id($storage->type);

	$table = new htmlobject_table_builder('iscsi_lun_name', '', '', '', 'luns');
	$arHead = array();

	$arHead['iscsi_lun_icon'] = array();
	$arHead['iscsi_lun_icon']['title'] ='';
	$arHead['iscsi_lun_icon']['sortable'] = false;

	$arHead['iscsi_lun_name'] = array();
	$arHead['iscsi_lun_name']['title'] ='Name';

	$arHead['iscsi_lun_devname'] = array();
	$arHead['iscsi_lun_devname']['title'] ='Lun';

	$arHead['iscsi_lun_size'] = array();
	$arHead['iscsi_lun_size']['title'] ='Size';

	$arHead['iscsi_lun_snap'] = array();
	$arHead['iscsi_lun_snap']['title'] ='Clone (name)';

	$arBody = array();
	$iscsi_count=0;
	$storage_icon="/openqrm/base/plugins/iscsi-storage/img/storage.png";

	$storage_export_list="storage/$storage_resource->id.iscsi.stat";
	if (file_exists($storage_export_list)) {
		$storage_vg_content=file($storage_export_list);
		foreach ($storage_vg_content as $index => $iscsi) {
			// find export name
			if (!strstr($iscsi, "#")) {
				$iscsi_line = trim($iscsi);
				$first_at_pos = strpos($iscsi_line, "@");
				$first_at_pos++;
				$iscsi_line_first_at_removed = substr($iscsi_line, $first_at_pos, strlen($iscsi_line)-$first_at_pos);
				$second_at_pos = strpos($iscsi_line_first_at_removed, "@");
				$second_at_pos++;
				$iscsi_line_second_at_removed = substr($iscsi_line_first_at_removed, $second_at_pos, strlen($iscsi_line_first_at_removed)-$second_at_pos);
				$third_at_pos = strpos($iscsi_line_second_at_removed, "@");
				$third_at_pos++;
				$iscsi_line_third_at_removed = substr($iscsi_line_second_at_removed, $third_at_pos, strlen($iscsi_line_second_at_removed)-$third_at_pos);
				$fourth_at_pos = strpos($iscsi_line_third_at_removed, "@");
				$fourth_at_pos++;
				$iscsi_line_fourth_at_removed = substr($iscsi_line_third_at_removed, $fourth_at_pos, strlen($iscsi_line_third_at_removed)-$fourth_at_pos);
				$fivth_at_pos = strpos($iscsi_line_fourth_at_removed, "@");
				$fivth_at_pos++;
				$iscsi_line_fivth_at_removed = substr($iscsi_line_fourth_at_removed, $fivth_at_pos, strlen($iscsi_line_fourth_at_removed)-$fivth_at_pos);
				$sixth_at_pos = strpos($iscsi_line_fivth_at_removed, "@");
				$sixth_at_pos++;
				$iscsi_line_sixth_at_removed = substr($iscsi_line_fivth_at_removed, $sixth_at_pos, strlen($iscsi_line_fivth_at_removed)-$sixth_at_pos);
				$seventh_at_pos = strpos($iscsi_line_sixth_at_removed, "@");
				$seventh_at_pos++;

				$iscsi_lun_id = trim(substr($iscsi_line, 0, $first_at_pos-1));
				$iscsi_lun_devname = trim(substr($iscsi_line_first_at_removed, 0, $second_at_pos-1));
				$iscsi_lun_name = basename($iscsi_lun_devname);
				$iscsi_lun_size = trim(substr($iscsi_line_second_at_removed, 0, $third_at_pos-1));

				// build the snap-shot input
				$iscsi_lun_snap = "<form action=\"$thisfile\" method=\"GET\">";
				$iscsi_lun_snap .= "<input type='hidden' name='iscsi_storage_id' value=$iscsi_storage_id>";
				$iscsi_lun_snap .= "<input type='hidden' name='iscsi_lun_name' value=$iscsi_lun_name>";
				$iscsi_lun_snap .= "<input type='text' name='iscsi_lun_snap_name' value='' size='10' maxlength='20'>";
				$iscsi_lun_snap .= "<input type='submit' name='action' value='snap'>";
				$iscsi_lun_snap .= "</form>";

				$arBody[] = array(
					'iscsi_lun_icon' => "<img width=24 height=24 src=$storage_icon>",
					'iscsi_lun_name' => $iscsi_lun_name,
					'iscsi_lun_devname' => $iscsi_lun_devname,
					'iscsi_lun_size' => $iscsi_lun_size." MB",
					'iscsi_lun_snap' => $iscsi_lun_snap,
				);
				$iscsi_count++;
			}
		}
	}

	// transfer some parameter in case we do not have a line in the table
	$table->add_headrow("<input type='hidden' name='iscsi_storage_id' value=$iscsi_storage_id>");

	$table->id = 'Tabelle';
	$table->css = 'htmlobject_table';
	$table->border = 1;
	$table->cellspacing = 0;
	$table->cellpadding = 3;
	$table->form_action = $thisfile;
	$table->identifier_type = "checkbox";
	$table->autosort = true;
	$table->head = $arHead;
	$table->body = $arBody;
	if ($OPENQRM_USER->role == "administrator") {
		$table->bottom = array('reload', 'remove');
		$table->identifier = 'iscsi_lun_name';
	}
	$table->max = $iscsi_count;

	// set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './tpl/' . 'iscsi-storage-luns.tpl.php');
	$t->setVar(array(
		'formaction' => $thisfile,
		'storage_name' => $storage->name,
		'lun_table' => $table->get_string(),
		'iscsi_lun_name' => htmlobject_input('iscsi_lun_name', array("value" => '', "label" => 'Lun Name'), 'text', 20),
		'iscsi_lun_size' => htmlobject_input('iscsi_lun_size', array("value" => '2000', "label" => 'Lun Size (MB)'), 'text', 20),
		'hidden_iscsi_storage_id' => "<input type='hidden' name='iscsi_storage_id' value=$iscsi_storage_id>",
		'submit' => htmlobject_input('action', array("value" => 'add', "label" => 'Add'), 'submit'),
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;
}





$output = array();

if(htmlobject_request('action') != '') {
	switch (htmlobject_request('action')) {
		case 'select':
			if (isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $id) {
					$output[] = array('label' => 'Iscsi Storage Admin', 'value' => iscsi_storage_display($id));
				}
			} else {
				$output[] = array('label' => 'Select', 'value' => iscsi_select_storage());
			}
			break;
		case 'reload':
			if (isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $id) {
					$output[] = array('label' => 'Iscsi Storage Admin', 'value' => iscsi_storage_display($id));
				}
			}
			break;

		case 'add':
			if (strlen($iscsi_storage_id)) {
					$output[] = array('label' => 'Iscsi Storage Admin', 'value' => iscsi_storage_display($iscsi_storage_id));
			} else {
				$output[] = array('label' => 'Select', 'value' => iscsi_select_storage());
			}
			break;

		case 'remove':
			if (strlen($iscsi_storage_id)) {
					$output[] = array('label' => 'Iscsi Storage Admin', 'value' => iscsi_storage_display($iscsi_storage_id));
			} else {
				$output[] = array('label' => 'Select', 'value' => iscsi_select_storage());
			}
			break;

		case 'snap':
			if (strlen($iscsi_storage_id)) {
				$output[] = array('label' => 'Iscsi Storage Admin', 'value' => iscsi_storage_display($iscsi_storage_id));
			} else {
				$output[] = array('label' => 'Select', 'value' => iscsi_select_storage());
			}
			break;

	}

} else if (strlen($iscsi_storage_id)) {
	$output[] = array('label' => 'Iscsi Storage Admin', 'value' => iscsi_storage_display($iscsi_storage_id));
} else  {
	$output[] = array('label' => 'Select', 'value' => iscsi_select_storage());
}


?>
<script type="text/javascript">
	$("#progressbar").remove();
</script>
<?php

echo htmlobject_tabmenu($output);

?>


