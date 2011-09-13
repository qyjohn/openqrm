<!doctype html>
<html lang="en">
<head>
	<title>SANBOOT Storage manager</title>
	<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
	<link rel="stylesheet" type="text/css" href="sanboot-storage.css" />
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
require_once "$RootDir/class/deployment.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/include/htmlobject.inc.php";


$sanboot_storage_id = htmlobject_request('sanboot_storage_id');
$sanboot_volume_group = htmlobject_request('sanboot_volume_group');
$sanboot_lun_name=htmlobject_request('sanboot_lun_name');
$sanboot_lun_snap_name=htmlobject_request('sanboot_lun_snap_name');
$sanboot_lun_snap_size=htmlobject_request('sanboot_lun_snap_size');
$sanboot_lun_resize=htmlobject_request('sanboot_lun_resize');
// to gather one of the deployment types within sanboot-storage
$sanboot_storage_type=htmlobject_request('type');

$action=htmlobject_request('action');
global $sanboot_storage_id;
global $sanboot_volume_group;
global $sanboot_lun_name;
global $sanboot_lun_snap_name;
global $sanboot_lun_resize;
global $sanboot_storage_type;

$refresh_delay=1;
$refresh_loop_max=20;


function redirect_vg($strMsg, $sanboot_storage_id) {
	global $thisfile;
	global $action;
	$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab=tab0&redirect=yes&action='.$action.'&identifier[]='.$sanboot_storage_id;
	echo "<meta http-equiv=\"refresh\" content=\"0; URL=$url\">";
	exit;
}

function redirect_lv($strMsg, $sanboot_storage_id, $sanboot_volume_group) {
	global $thisfile;
	global $action;
	$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab=tab0&redirect=yes&action='.$action.'&sanboot_storage_id='.$sanboot_storage_id.'&identifier[]='.$sanboot_volume_group;
	echo "<meta http-equiv=\"refresh\" content=\"0; URL=$url\">";
	exit;
}

function redirect_sanbootgmt($strMsg, $sanboot_storage_id, $sanboot_volume_group) {
	global $thisfile;
	global $action;
	$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab=tab0&redirect=yes&action='.$action.'&sanboot_storage_id='.$sanboot_storage_id.'&sanboot_volume_group='.$sanboot_volume_group;
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
							$deployment = new deployment();
							$deployment->get_instance_by_id($storage->type);
							$storage_resource = new resource();
							$storage_resource->get_instance_by_id($storage->resource_id);
							// post vg status
							$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/sanboot-storage/bin/openqrm-sanboot-storage post_vg -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
							// remove current stat file
							$storage_resource_id = $storage_resource->id;
							$statfile="storage/".$storage_resource_id.".vg.stat";
							if (file_exists($statfile)) {
								unlink($statfile);
							}
							// send command
							$storage_resource->send_command($storage_resource->ip, $resource_command);
							// and wait for the resulting statfile
							if (!wait_for_statfile($statfile)) {
								$redir_msg = "Error during selecting volume group ! Please check the Event-Log";
							} else {
								$redir_msg = "Displaying volume groups on storage id $id";
							}
							redirect_vg($redir_msg, $id);
						}
					}
					break;

				case 'select-vg':
					if (isset($_REQUEST['identifier'])) {
						foreach($_REQUEST['identifier'] as $sanboot_volume_group) {
							show_progressbar();
							$storage = new storage();
							$storage->get_instance_by_id($sanboot_storage_id);
							$deployment = new deployment();
							$deployment->get_instance_by_id($storage->type);
							$storage_resource = new resource();
							$storage_resource->get_instance_by_id($storage->resource_id);
							// post lv status
							$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/sanboot-storage/bin/openqrm-sanboot-storage post_lv -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password -v $sanboot_volume_group -t $deployment->type";
							// remove current stat file
							$storage_resource_id = $storage_resource->id;
							$statfile="storage/".$storage_resource_id.".".$sanboot_volume_group.".lv.stat";
							if (file_exists($statfile)) {
								unlink($statfile);
							}
							// send command
							$storage_resource->send_command($storage_resource->ip, $resource_command);
							// and wait for the resulting statfile
							if (!wait_for_statfile($statfile)) {
								$redir_msg = "Error during selecting volume group ! Please check the Event-Log";
							} else {
								$redir_msg = "Displaying volume groups on storage id $sanboot_storage_id";
							}
							redirect_lv($redir_msg, $sanboot_storage_id, $sanboot_volume_group);
						}
					}
					break;


				case 'add':
					$sanboot_lun_name = htmlobject_request('sanboot_lun_name');
					show_progressbar();
					if (!strlen($sanboot_lun_name)) {
						$redir_msg = "Got emtpy logical volume name. Not adding ...";
						redirect_sanbootgmt($redir_msg, $sanboot_storage_id, $sanboot_volume_group);
						exit(0);
					} else if (!validate_input($sanboot_lun_name, 'string')) {
						$redir_msg = "Got invalid logical volume name. Not adding ...<br>(allowed characters are [a-z][A-z][0-9].-_)";
						redirect_sanbootgmt($redir_msg, $sanboot_storage_id, $sanboot_volume_group);
						exit(0);
					}
					$sanboot_lun_size = htmlobject_request('sanboot_lun_size');
					if (!strlen($sanboot_lun_size)) {
						$sanboot_lun_size=2000;
					} else if (!validate_input($sanboot_lun_size, 'number')) {
						$redir_msg = "Got invalid logical volume size. Not adding ...";
						redirect_sanbootgmt($redir_msg, $sanboot_storage_id, $sanboot_volume_group);
						exit(0);
					}
					$storage = new storage();
					$storage->get_instance_by_id($sanboot_storage_id);
					$storage_resource = new resource();
					$storage_resource->get_instance_by_id($storage->resource_id);
					$storage_deployment = new deployment();
					$storage_deployment->get_instance_by_id($storage->type);
					// in case of sanboot-iscsi we have to send a password when adding a lun
					if (!strcmp($storage_deployment->type, "iscsi-san-deployment")) {
						$image = new image();
						// generate a password for the image
						$image_password = $image->generatePassword(12);
						$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/sanboot-storage/bin/openqrm-sanboot-storage add -n $sanboot_lun_name -v $sanboot_volume_group -t $storage_deployment->type -m $sanboot_lun_size -i $image_password -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
					} else {
						$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/sanboot-storage/bin/openqrm-sanboot-storage add -n $sanboot_lun_name -v $sanboot_volume_group -t $storage_deployment->type -m $sanboot_lun_size -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
					}
					// remove current stat file
					$storage_resource_id = $storage_resource->id;
					$statfile="storage/".$storage_resource_id.".".$sanboot_volume_group.".lv.stat";
					if (file_exists($statfile)) {
						unlink($statfile);
					}
					// send command
					$storage_resource->send_command($storage_resource->ip, $resource_command);
					// and wait for the resulting statfile
					if (!wait_for_statfile($statfile)) {
						$redir_msg = "Error during adding logical volume $sanboot_lun_name to Volume group $sanboot_volume_group ! Please check the Event-Log";
					} else {
						$redir_msg = "Added volume $sanboot_lun_name to Volume group $sanboot_volume_group";
					}
					redirect_sanbootgmt($redir_msg, $sanboot_storage_id, $sanboot_volume_group);
					break;

				case 'remove':
					if (isset($_REQUEST['identifier'])) {
						show_progressbar();
						foreach($_REQUEST['identifier'] as $sanboot_lun_name) {
							$storage = new storage();
							$storage->get_instance_by_id($sanboot_storage_id);
							$storage_resource = new resource();
							$storage_resource->get_instance_by_id($storage->resource_id);
							$storage_deployment = new deployment();
							$storage_deployment->get_instance_by_id($storage->type);
							$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/sanboot-storage/bin/openqrm-sanboot-storage remove -n $sanboot_lun_name -v $sanboot_volume_group -t $storage_deployment->type -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
							// remove current stat file
							$storage_resource_id = $storage_resource->id;
							$statfile="storage/".$storage_resource_id.".".$sanboot_volume_group.".lv.stat";
							if (file_exists($statfile)) {
								unlink($statfile);
							}
							// send command
							$storage_resource->send_command($storage_resource->ip, $resource_command);
							// and wait for the resulting statfile
							if (!wait_for_statfile($statfile)) {
								$redir_msg .= "Error during removing volume $sanboot_lun_name from Volume group $sanboot_volume_group ! Please check the Event-Log<br>";
							} else {
								$redir_msg .= "Removed volume $sanboot_lun_name from Volume group $sanboot_volume_group<br>";
							}
						}
						redirect_sanbootgmt($redir_msg, $sanboot_storage_id, $sanboot_volume_group);
					} else {
						$redir_msg = "No SANBOOT location selected. Skipping removal !";
						redirect_sanbootgmt($redir_msg, $sanboot_storage_id, $sanboot_volume_group);
					}
					break;


				case 'reload':
					show_progressbar();
					$storage = new storage();
					$storage->get_instance_by_id($sanboot_storage_id);
					$deployment = new deployment();
					$deployment->get_instance_by_id($storage->type);
					$storage_resource = new resource();
					$storage_resource->get_instance_by_id($storage->resource_id);
					// post lv status
					$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/sanboot-storage/bin/openqrm-sanboot-storage post_lv -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password -v $sanboot_volume_group -t $deployment->type";
					// remove current stat file
					$storage_resource_id = $storage_resource->id;
					$statfile="storage/".$storage_resource_id.".".$sanboot_volume_group.".lv.stat";
					if (file_exists($statfile)) {
						unlink($statfile);
					}
					// send command
					$storage_resource->send_command($storage_resource->ip, $resource_command);
					// and wait for the resulting statfile
					if (!wait_for_statfile($statfile)) {
						$redir_msg = "Error during displaying logical volumes on Volume group $sanboot_volume_group ! Please check the Event-Log";
					} else {
						$redir_msg = "Displaying logical volumes on Volume group $sanboot_volume_group";
					}
					redirect_sanbootgmt($redir_msg, $sanboot_storage_id, $sanboot_volume_group);
					break;


				case 'snap':
				case 'clone':
					$sanboot_action = htmlobject_request('action');
					if (strlen($sanboot_lun_snap_name)) {
						show_progressbar();
						if (!strlen($sanboot_lun_name)) {
							$redir_msg = "Got emtpy logical volume name. Not adding ...";
							redirect_sanbootgmt($redir_msg, $sanboot_storage_id, $sanboot_volume_group);
							exit(0);
						} else if (!validate_input($sanboot_lun_name, 'string')) {
							$redir_msg = "Got invalid logical volume name. Not adding ...<br>(allowed characters are [a-z][A-z][0-9].-_)";
							redirect_sanbootgmt($redir_msg, $sanboot_storage_id, $sanboot_volume_group);
							exit(0);
						}

						if (!strlen($sanboot_lun_snap_name)) {
							$redir_msg = "Got emtpy logical volume clone name. Not adding ...";
							redirect_sanbootgmt($redir_msg, $sanboot_storage_id, $sanboot_volume_group);
							exit(0);
						} else if (!validate_input($sanboot_lun_snap_name, 'string')) {
							$redir_msg = "Got invalid logical volume clone name. Not adding ...<br>(allowed characters are [a-z][A-z][0-9].-_)";
							redirect_sanbootgmt($redir_msg, $sanboot_storage_id, $sanboot_volume_group);
							exit(0);
						}

						if (!strlen($sanboot_lun_snap_size)) {
							$sanboot_lun_snap_size=5000;
						} else if (!validate_input($sanboot_lun_snap_size, 'number')) {
							$redir_msg = "Got invalid logical volume clone size. Not adding ...";
							redirect_sanbootgmt($redir_msg, $sanboot_storage_id, $sanboot_volume_group);
							exit(0);
						}
						// snap/clone
						$storage = new storage();
						$storage->get_instance_by_id($sanboot_storage_id);
						$storage_resource = new resource();
						$storage_resource->get_instance_by_id($storage->resource_id);
						$deployment = new deployment();
						$deployment->get_instance_by_id($storage->type);
						// in case of sanboot-iscsi we have to send a password when adding a lun
						if (!strcmp($deployment->type, "iscsi-san-deployment")) {
							$image = new image();
							// generate a password for the image
							$image_password = $image->generatePassword(12);
							$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/sanboot-storage/bin/openqrm-sanboot-storage $sanboot_action -n $sanboot_lun_name -v $sanboot_volume_group -t $deployment->type -s $sanboot_lun_snap_name -m $sanboot_lun_snap_size -i $image_password -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
						} else {
							$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/sanboot-storage/bin/openqrm-sanboot-storage $sanboot_action -n $sanboot_lun_name -v $sanboot_volume_group -t $deployment->type -s $sanboot_lun_snap_name -m $sanboot_lun_snap_size -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
						}
						// remove current stat file
						$storage_resource_id = $storage_resource->id;
						$statfile="storage/".$storage_resource_id.".".$sanboot_volume_group.".lv.stat";
						if (file_exists($statfile)) {
							unlink($statfile);
						}
						// send command
						$storage_resource->send_command($storage_resource->ip, $resource_command);
						// and wait for the resulting statfile
						if (!wait_for_statfile($statfile)) {
							$redir_msg = "Error during snapshotting volume $sanboot_lun_name -> $sanboot_lun_snap_name on Volume Group $sanboot_volume_group ! Please check the Event-Log";
						} else {
							$redir_msg = "Created snapshot of volume $sanboot_lun_name -> $sanboot_lun_snap_name on Volume Group $sanboot_volume_group";
						}
						redirect_sanbootgmt($redir_msg, $sanboot_storage_id, $sanboot_volume_group);
					} else {
						$redir_msg = "Got empty name. Skipping snapshot procedure !";
						redirect_sanbootgmt($redir_msg, $sanboot_storage_id, $sanboot_volume_group);
					}
					break;




				case 'resize':
					show_progressbar();
					if (!strlen($sanboot_lun_name)) {
						$redir_msg = "Got emtpy logical volume name. Not resizing ...";
						redirect_sanbootgmt($redir_msg, $sanboot_storage_id, $sanboot_volume_group);
						exit(0);
					} else if (!validate_input($sanboot_lun_name, 'string')) {
						$redir_msg = "Got invalid logical volume name. Not resizing ...<br>(allowed characters are [a-z][A-z][0-9].-_)";
						redirect_sanbootgmt($redir_msg, $sanboot_storage_id, $sanboot_volume_group);
						exit(0);
					}
					if (!strlen($sanboot_lun_resize)) {
						$sanboot_lun_resize=5000;
					} else if (!validate_input($sanboot_lun_resize, 'number')) {
						$redir_msg = "Got invalid logical volume resize value. Not resizing ...";
						redirect_sanbootgmt($redir_msg, $sanboot_storage_id, $sanboot_volume_group);
						exit(0);
					}
					// snap
					$storage = new storage();
					$storage->get_instance_by_id($sanboot_storage_id);
					$storage_resource = new resource();
					$storage_resource->get_instance_by_id($storage->resource_id);
					$deployment = new deployment();
					$deployment->get_instance_by_id($storage->type);
					// in case of sanboot-iscsi we have to send a password when adding a lun
					if (!strcmp($deployment->type, "iscsi-san-deployment")) {
						$image = new image();
						// generate a password for the image
						$image_password = $image->generatePassword(12);
						$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/sanboot-storage/bin/openqrm-sanboot-storage resize -n $sanboot_lun_name -v $sanboot_volume_group -t $deployment->type -m $sanboot_lun_resize -i $image_password -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
					} else {
						$resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/sanboot-storage/bin/openqrm-sanboot-storage resize -n $sanboot_lun_name -v $sanboot_volume_group -t $deployment->type -m $sanboot_lun_resize -u $OPENQRM_ADMIN->name -p $OPENQRM_ADMIN->password";
					}
					// remove current stat file
					$storage_resource_id = $storage_resource->id;
					$statfile="storage/".$storage_resource_id.".".$sanboot_volume_group.".lv.stat";
					if (file_exists($statfile)) {
						unlink($statfile);
					}
					// send command
					$storage_resource->send_command($storage_resource->ip, $resource_command);
					// and wait for the resulting statfile
					if (!wait_for_statfile($statfile)) {
						$redir_msg = "Error during resizing volume $sanboot_lun_name on Volume Group $sanboot_volume_group ! Please check the Event-Log";
					} else {
						$redir_msg = "Resized volume $sanboot_lun_name on Volume Group $sanboot_volume_group";
					}
					redirect_sanbootgmt($redir_msg, $sanboot_storage_id, $sanboot_volume_group);
					break;

			}
		}
	}
}




function sanboot_select_storage() {
	global $OPENQRM_USER;
	global $thisfile;
	global $sanboot_storage_type;

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
	switch ($sanboot_storage_type) {
		case 'aoe':
			$t_deployment->get_instance_by_type("aoe-san-deployment");
			break;
		case 'iscsi':
			$t_deployment->get_instance_by_type("iscsi-san-deployment");
			break;

	}
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
		$storage_icon="/openqrm/base/plugins/sanboot-storage/img/storage.png";
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
		$storage_count++;
	}

	$table->id = 'Tabelle';
	$table->css = 'htmlobject_table';
	$table->border = 1;
	$table->cellspacing = 0;
	$table->cellpadding = 3;
	$table->identifier_type = "radio";
	$table->form_action = $thisfile;
	$table->head = $arHead;
	$table->body = $arBody;
	$table->add_headrow("<input type='hidden' name='type' value=$sanboot_storage_type>");

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
		$box_content = '<br><br><br><br>Please create a '.$t_deployment->storagedescription.' first!<br>';
		$box_content .= '<a href="/openqrm/base/server/storage/storage-new.php?currenttab=tab1"><b>New storage</b></a><br>';
		$box->content = $box_content;
		$disp = $box->get_string();
	}

   // set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './tpl/' . 'sanboot-storage-select.tpl.php');
	$t->setVar(array(
		'formaction' => $thisfile,
		'storage_server_table' => $disp,
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;
}


function sanboot_storage_display($sanboot_storage_id) {
	global $OPENQRM_USER;
	global $thisfile;

	$storage = new storage();
	$storage->get_instance_by_id($sanboot_storage_id);
	$storage_resource = new resource();
	$storage_resource->get_instance_by_id($storage->resource_id);
	$deployment = new deployment();
	$deployment->get_instance_by_id($storage->type);

	$table = new htmlobject_table_identifiers_checked('storage_id');
	$arHead = array();
	$arHead['storage_state'] = array();
	$arHead['storage_state']['title'] ='';

	$arHead['storage_icon'] = array();
	$arHead['storage_icon']['title'] ='';

	$arHead['storage_id'] = array();
	$arHead['storage_id']['title'] ='ID';

	$arHead['storage_name'] = array();
	$arHead['storage_name']['title'] ='Name';

	$arHead['storage_resource_id'] = array();
	$arHead['storage_resource_id']['title'] ='Res.ID';

	$arHead['storage_resource_ip'] = array();
	$arHead['storage_resource_ip']['title'] ='Ip';

	$arHead['storage_type'] = array();
	$arHead['storage_type']['title'] ='Type';

	$arHead['storage_comment'] = array();
	$arHead['storage_comment']['title'] ='Comment';

	$arHead['storage_capabilities'] = array();
	$arHead['storage_capabilities']['title'] ='Capabilities';

	$storage_count=1;
	$arBody = array();
	$resource_icon_default="/openqrm/base/img/resource.png";
	$storage_icon="/openqrm/base/plugins/sanboot-storage/img/storage.png";
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
		'storage_capabilities' => $storage->capabilities,
	);


	$table->id = 'Tabelle';
	$table->css = 'htmlobject_table';
	$table->border = 1;
	$table->cellspacing = 0;
	$table->cellpadding = 3;
	$table->form_action = $thisfile;
	$table->sort = '';
	$table->head = $arHead;
	$table->body = $arBody;
	$table->max = $storage_count;


	// vg table
	$table1 = new htmlobject_table_builder('vg_name', '', '', '', 'vgs');
	$arHead1 = array();
	$arHead1['vg_icon'] = array();
	$arHead1['vg_icon']['title'] ='';
	$arHead1['vg_icon']['sortable'] = false;

	$arHead1['vg_name'] = array();
	$arHead1['vg_name']['title'] ='Name';

	$arHead1['vg_pv'] = array();
	$arHead1['vg_pv']['title'] ='PV';

	$arHead1['vg_lv'] = array();
	$arHead1['vg_lv']['title'] ='LV';

	$arHead1['vg_sn'] = array();
	$arHead1['vg_sn']['title'] ='SN';

	$arHead1['vg_attr'] = array();
	$arHead1['vg_attr']['title'] ='Attr';

	$arHead1['vg_vsize'] = array();
	$arHead1['vg_vsize']['title'] ='VSize';

	$arHead1['vg_vfree'] = array();
	$arHead1['vg_vfree']['title'] ='VFree';

	$arBody1 = array();
	$vg_count=0;
	$storage_vg_list="storage/$storage_resource->id.vg.stat";
	if (file_exists($storage_vg_list)) {
		$storage_vg_content=file($storage_vg_list);
		foreach ($storage_vg_content as $index => $sanboot) {
			$vg_line = trim($sanboot);

			$first_at_pos = strpos($vg_line, "@");
			$first_at_pos++;
			$vg_line_first_at_removed = substr($vg_line, $first_at_pos, strlen($vg_line)-$first_at_pos);
			$second_at_pos = strpos($vg_line_first_at_removed, "@");
			$second_at_pos++;
			$vg_line_second_at_removed = substr($vg_line_first_at_removed, $second_at_pos, strlen($vg_line_first_at_removed)-$second_at_pos);
			$third_at_pos = strpos($vg_line_second_at_removed, "@");
			$third_at_pos++;
			$vg_line_third_at_removed = substr($vg_line_second_at_removed, $third_at_pos, strlen($vg_line_second_at_removed)-$third_at_pos);
			$fourth_at_pos = strpos($vg_line_third_at_removed, "@");
			$fourth_at_pos++;
			$vg_line_fourth_at_removed = substr($vg_line_third_at_removed, $fourth_at_pos, strlen($vg_line_third_at_removed)-$fourth_at_pos);
			$fivth_at_pos = strpos($vg_line_fourth_at_removed, "@");
			$fivth_at_pos++;
			$vg_line_fivth_at_removed = substr($vg_line_fourth_at_removed, $fivth_at_pos, strlen($vg_line_fourth_at_removed)-$fivth_at_pos);
			$sixth_at_pos = strpos($vg_line_fivth_at_removed, "@");
			$sixth_at_pos++;
			$vg_line_sixth_at_removed = substr($vg_line_fivth_at_removed, $sixth_at_pos, strlen($vg_line_fivth_at_removed)-$sixth_at_pos);
			$seventh_at_pos = strpos($vg_line_sixth_at_removed, "@");
			$seventh_at_pos++;

			$vg_name = trim(substr($vg_line, 0, $first_at_pos-1));
			$vg_pv = trim(substr($vg_line_first_at_removed, 0, $second_at_pos-1));
			$vg_lv = trim(substr($vg_line_second_at_removed, 0, $third_at_pos-1));
			$vg_sn = trim(substr($vg_line_third_at_removed, 0, $fourth_at_pos-1));
			$vg_attr = trim(substr($vg_line_fourth_at_removed, 0, $fivth_at_pos-1));
			$vg_vsize = trim(substr($vg_line_fivth_at_removed, 0, $sixth_at_pos-1));
			$vg_vfree = trim(substr($vg_line_sixth_at_removed, 0, $seventh_at_pos-1));

			$arBody1[] = array(
				'vg_icon' => "<img width=24 height=24 src=$storage_icon>",
				'vg_name' => $vg_name,
				'vg_pv' => $vg_pv,
				'vg_lv' => $vg_lv,
				'vg_sn' => $vg_sn,
				'vg_attr' => $vg_attr,
				'vg_vsize' => $vg_vsize,
				'vg_vfree' => $vg_vfree,
			);
			$vg_count++;
		}
	}
	$table1->add_headrow("<input type='hidden' name='sanboot_storage_id' value=$sanboot_storage_id>");
	$table1->id = 'Tabelle';
	$table1->css = 'htmlobject_table';
	$table1->border = 1;
	$table1->cellspacing = 0;
	$table1->cellpadding = 3;
	$table1->form_action = $thisfile;
	$table1->identifier_type = "radio";
	$table1->autosort = true;
	$table1->head = $arHead1;
	$table1->body = $arBody1;
	if ($OPENQRM_USER->role == "administrator") {
		$table1->bottom = array('select-vg');
		$table1->identifier = 'vg_name';
	}
	$table1->max = $vg_count;

	// set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './tpl/' . 'sanboot-storage-vgs.tpl.php');
	$t->setVar(array(
		'formaction' => $thisfile,
		'storage_name' => $storage->name,
		'storage_table' => $table->get_string(),
		'vg_table' => $table1->get_string(),
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;
}






function sanboot_storage_lv_display($sanboot_storage_id, $sanboot_volume_group) {
	global $OPENQRM_USER;
	global $thisfile;
	global $RootDir;

	$storage = new storage();
	$storage->get_instance_by_id($sanboot_storage_id);
	$storage_resource = new resource();
	$storage_resource->get_instance_by_id($storage->resource_id);
	$deployment = new deployment();
	$deployment->get_instance_by_id($storage->type);

	// sanboot table
	$table = new htmlobject_table_builder('sanboot_lun_name', '', '', '', 'luns');
	$arHead = array();
	$arHead['sanboot_lun_icon'] = array();
	$arHead['sanboot_lun_icon']['title'] ='';
	$arHead['sanboot_lun_icon']['sortable'] = false;

	$arHead['sanboot_lun_name'] = array();
	$arHead['sanboot_lun_name']['title'] ='Lun';

	$arHead['sanboot_lun_attr'] = array();
	$arHead['sanboot_lun_attr']['title'] ='Attr';

	$arHead['sanboot_lun_lsize'] = array();
	$arHead['sanboot_lun_lsize']['title'] ='LSize';

	$arHead['sanboot_lun_rsize'] = array();
	$arHead['sanboot_lun_rsize']['title'] ='Resize (+ MB)';
	$arHead['sanboot_lun_rsize']['sortable'] = false;

	$arHead['sanboot_lun_snap'] = array();
	$arHead['sanboot_lun_snap']['title'] ='Snap/Clone (name + size)';
	$arHead['sanboot_lun_snap']['sortable'] = false;

	$arBody = array();
	$sanboot_lun_count=0;
	$storage_icon="/openqrm/base/plugins/sanboot-storage/img/storage.png";
	$storage_export_list="storage/".$storage->resource_id.".".$sanboot_volume_group.".lv.stat";
	if (file_exists($storage_export_list)) {
		$storage_vg_content=file($storage_export_list);
		foreach ($storage_vg_content as $index => $sanboot) {
			$sanboot_line = trim($sanboot);

			$first_at_pos = strpos($sanboot_line, "@");
			$first_at_pos++;
			$sanboot_line_first_at_removed = substr($sanboot_line, $first_at_pos, strlen($sanboot_line)-$first_at_pos);
			$second_at_pos = strpos($sanboot_line_first_at_removed, "@");
			$second_at_pos++;
			$sanboot_line_second_at_removed = substr($sanboot_line_first_at_removed, $second_at_pos, strlen($sanboot_line_first_at_removed)-$second_at_pos);
			$third_at_pos = strpos($sanboot_line_second_at_removed, "@");
			$third_at_pos++;
			$sanboot_line_third_at_removed = substr($sanboot_line_second_at_removed, $third_at_pos, strlen($sanboot_line_second_at_removed)-$third_at_pos);
			$fourth_at_pos = strpos($sanboot_line_third_at_removed, "@");
			$fourth_at_pos++;
			$sanboot_line_fourth_at_removed = substr($sanboot_line_third_at_removed, $fourth_at_pos, strlen($sanboot_line_third_at_removed)-$fourth_at_pos);
			$fivth_at_pos = strpos($sanboot_line_fourth_at_removed, "@");
			$fivth_at_pos++;
			$sanboot_line_fivth_at_removed = substr($sanboot_line_fourth_at_removed, $fivth_at_pos, strlen($sanboot_line_fourth_at_removed)-$fivth_at_pos);
			$sixth_at_pos = strpos($sanboot_line_fivth_at_removed, "@");
			$sixth_at_pos++;
			$sanboot_line_sixth_at_removed = substr($sanboot_line_fivth_at_removed, $sixth_at_pos, strlen($sanboot_line_fivth_at_removed)-$sixth_at_pos);
			$seventh_at_pos = strpos($sanboot_line_sixth_at_removed, "@");
			$seventh_at_pos++;

			$sanboot_lun_name = trim(substr($sanboot_line, 0, $first_at_pos-1));
			$sanboot_lun_vol = trim(substr($sanboot_line_first_at_removed, 0, $second_at_pos-1));
			$sanboot_lun_attr = trim(substr($sanboot_line_second_at_removed, 0, $third_at_pos-1));
			$sanboot_lun_lsize = trim(substr($sanboot_line_third_at_removed, 0, $fourth_at_pos-1));

			// build the resize input
			$sanboot_lun_rsize = "<form action=\"$thisfile\" method=\"GET\">";
			$sanboot_lun_rsize .= "<input type='hidden' name='sanboot_storage_id' value=$sanboot_storage_id>";
			$sanboot_lun_rsize .= "<input type='hidden' name='sanboot_volume_group' value=$sanboot_volume_group>";
			$sanboot_lun_rsize .= "<input type='hidden' name='sanboot_lun_name' value=$sanboot_lun_name>";
			$sanboot_lun_rsize .= "<input type='text' name='sanboot_lun_resize' value='' size='5' maxlength='10'> MB ";
			$sanboot_lun_rsize .= "<input type='submit' name='action' value='resize'>";
			$sanboot_lun_rsize .= "</form>";

			// build the snap-shot input
			$sanboot_lun_snap = "<form action=\"$thisfile\" method=\"GET\">";
			$sanboot_lun_snap .= "<input type='hidden' name='sanboot_storage_id' value=$sanboot_storage_id>";
			$sanboot_lun_snap .= "<input type='hidden' name='sanboot_volume_group' value=$sanboot_volume_group>";
			$sanboot_lun_snap .= "<input type='hidden' name='sanboot_lun_name' value=$sanboot_lun_name>";
			$sanboot_lun_snap .= "<input type='text' name='sanboot_lun_snap_name' value='' size='10' maxlength='20'>";
			$sanboot_lun_snap .= "<input type='text' name='sanboot_lun_snap_size' value='' size='5' maxlength='10'> MB ";
			// check if to show the snap button
			if (!strstr($sanboot_lun_attr, "swi")) {
				$sanboot_lun_snap .= "<input type='submit' name='action' value='snap'>";
			} else {
				$sanboot_lun_snap .= "<input type='submit' name='action' value='snap' disabled='true'>";
			}
			$sanboot_lun_snap .= "<input type='submit' name='action' value='clone'>";
			$sanboot_lun_snap .= "</form>";


			$arBody[] = array(
				'sanboot_lun_icon' => "<img width=24 height=24 src=$storage_icon>",
				'sanboot_lun_name' => $sanboot_lun_name,
				'sanboot_lun_attr' => $sanboot_lun_attr,
				'sanboot_lun_lsize' => $sanboot_lun_lsize,
				'sanboot_lun_rsize' => "<nobr>".$sanboot_lun_rsize."</nobr>",
				'sanboot_lun_snap' => "<nobr>".$sanboot_lun_snap."</nobr>",
			);
			$sanboot_lun_count++;
		}
	}

	$table->add_headrow("<input type='hidden' name='sanboot_storage_id' value=$sanboot_storage_id><input type='hidden' name='sanboot_volume_group' value=$sanboot_volume_group>");
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
		$table->identifier = 'sanboot_lun_name';
	}
	$table->max = $sanboot_lun_count;


	// set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './tpl/' . 'sanboot-storage-luns.tpl.php');
	$t->setVar(array(
		'formaction' => $thisfile,
		'storage_name' => $storage->name,
		'sanboot_volume_group' => $sanboot_volume_group,
		'lun_table' => $table->get_string(),
		'sanboot_lun_name' => htmlobject_input('sanboot_lun_name', array("value" => '', "label" => 'Lun Name'), 'text', 20),
		'sanboot_lun_size' => htmlobject_input('sanboot_lun_size', array("value" => '2000', "label" => 'Lun Size (MB)'), 'text', 20),
		'hidden_sanboot_volume_group' => "<input type='hidden' name='sanboot_volume_group' value=$sanboot_volume_group>",
		'hidden_sanboot_storage_id' => "<input type='hidden' name='sanboot_storage_id' value=$sanboot_storage_id>",
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
					$output[] = array('label' => 'Sanboot Storage Admin', 'value' => sanboot_storage_display($id));
				}
			} else {
				$output[] = array('label' => 'Select', 'value' => sanboot_select_storage());
			}
			break;

		case 'select-vg':
			if (isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $sanboot_volume_group) {
					$output[] = array('label' => $sanboot_volume_group, 'value' => sanboot_storage_lv_display($sanboot_storage_id, $sanboot_volume_group));
				}
			} else {
				$output[] = array('label' => 'Sanboot Storage Admin', 'value' => sanboot_storage_display($sanboot_storage_id));
			}
			break;

		case 'add':
			$output[] = array('label' => $sanboot_volume_group, 'value' => sanboot_storage_lv_display($sanboot_storage_id, $sanboot_volume_group));
			break;

		case 'remove':
			$output[] = array('label' => $sanboot_volume_group, 'value' => sanboot_storage_lv_display($sanboot_storage_id, $sanboot_volume_group));
			break;

		case 'reload':
			$output[] = array('label' => $sanboot_volume_group, 'value' => sanboot_storage_lv_display($sanboot_storage_id, $sanboot_volume_group));
			break;

		case 'snap':
			$output[] = array('label' => $sanboot_volume_group, 'value' => sanboot_storage_lv_display($sanboot_storage_id, $sanboot_volume_group));
			break;

		case 'resize':
			$output[] = array('label' => $sanboot_volume_group, 'value' => sanboot_storage_lv_display($sanboot_storage_id, $sanboot_volume_group));
			break;


	}

} else if (strlen($sanboot_volume_group)) {
	$output[] = array('label' => 'Logical Volume Admin', 'value' => sanboot_storage_lv_display($sanboot_storage_id, $sanboot_volume_group));
} else if (strlen($sanboot_storage_id)) {
	$output[] = array('label' => 'Sanboot Storage Admin', 'value' => sanboot_storage_display($sanboot_storage_id));
} else  {
	$output[] = array('label' => 'Select', 'value' => sanboot_select_storage());
}


?>
<style>
	.htmlobject_tab_box {
		width:800px;
	}
</style>
<script type="text/javascript">
	$("#progressbar").remove();
</script>
<?php

echo htmlobject_tabmenu($output);

?>


