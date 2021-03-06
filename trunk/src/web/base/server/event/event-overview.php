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

#error_reporting(0);
$thisfile = basename($_SERVER['PHP_SELF']);

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/include/htmlobject.inc.php";

global $OPENQRM_SERVER_BASE_DIR;
$event = new event();
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $event;

$currenttab=htmlobject_request('currenttab');

function redirect($strMsg) {
	global $thisfile;
	global $currenttab;
	$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab='.$currenttab;
	header("Location: $url");
	exit;
}




if(htmlobject_request('action') != '') {
	$strMsg = '';
	$return_msg = '';
	switch (htmlobject_request('action')) {
			case '删除':
				$event = new event();
				if(isset($_REQUEST['identifier'])) {
					foreach($_REQUEST['identifier'] as $id) {
						// remove eventual errors
						$revent = new event();
						$revent->get_instance_by_id($id);
						if (strstr($revent->description, "ERROR running token")) {
							$error_token = str_replace("ERROR running token ", "", $revent->description);
							$cmd_file = $RootDir."/server/event/errors/".$error_token.".cmd";
							$error_file = $RootDir."/server/event/errors/".$error_token.".out";
							if (file_exists($cmd_file)) {
								if (!unlink($cmd_file)) {
									$strMsg .= "无法删除命令文件 $cmd_file 。<br>";
								}
							}
							if (file_exists($error_file)) {
								if (!unlink($error_file)) {
									$strMsg .= "无法删除错误文件  $error_file 。<br>";
								}
							}
						}
						$return_msg .= $event->remove($id);
						$strMsg .= "事件 $id 已经被删除。<br>";
					}
				}
				redirect($strMsg);
				break;
		case '确认':
			$event = new event();
			if(isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $id) {
					$event_fields=array();
					$event_fields["event_status"]=1;
					$return_msg .= $event->update($id, $event_fields);
					$strMsg .= "事件 $id 已经被确认。<br>";
				}
			}
			redirect($strMsg);
			break;

		case 'rerun':
			$token = htmlobject_request('token');
			$event_id = htmlobject_request('event_id');
			$rerun_event = new event();
			$rerun_event->get_instance_by_id($event_id);
			$event_fields=array();
			$event_fields["event_priority"]=4;
			$strMsg .= "Re-running token ".$token." / Event ID ".$event_id."<br>";
			$event->log("event-action", $_SERVER['REQUEST_TIME'], 5, "event-overview.php", "Re-Running command $token", "", "", 0, 0, 0);
			$rerun_command = "mv -f ".$OPENQRM_SERVER_BASE_DIR."/openqrm/web/base/server/event/errors/".$token.".cmd ".$OPENQRM_SERVER_BASE_DIR."/openqrm/var/spool/openqrm-queue.".$token." && rm -f ".$OPENQRM_SERVER_BASE_DIR."/openqrm/web/base/server/event/errors/".$token.".out";
			shell_exec($rerun_command);
			$rerun_event->update($event_id, $event_fields);
			redirect($strMsg);
			break;
	}
}



// html header must be below actions since they return single values without
// any additional html output
?>

<!doctype html>
<html lang="en">
<head>
<title>openQRM 事件概览</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></meta>
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
<link rel="stylesheet" type="text/css" href="event.css" />
<link type="text/css" href="/openqrm/base/js/jquery/development-bundle/themes/smoothness/ui.all.css" rel="stylesheet" />
<script type="text/javascript" src="/openqrm/base/js/jquery/js/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="/openqrm/base/js/jquery/js/jquery-ui-1.7.1.custom.min.js"></script>
<script type="text/javascript" src="/openqrm/base/js/interface/interface.js"></script>

<?php




function event_display() {
	global $OPENQRM_USER;
	global $thisfile;

	$event_tmp = new event();
	$table = new htmlobject_db_table('event_id', 'DESC');

	$arHead = array();
	$arHead['event_priority'] = array();
	$arHead['event_priority']['title'] ='状态';

	$arHead['event_id'] = array();
	$arHead['event_id']['title'] ='编号';

	$arHead['event_time'] = array();
	$arHead['event_time']['title'] ='时间';

	$arHead['event_source'] = array();
	$arHead['event_source']['title'] ='来源';

	$arHead['event_description'] = array();
	$arHead['event_description']['title'] ='描述';
	$arHead['event_description']['sortable'] = false;

	$arBody = array();
	$event_array = $event_tmp->display_overview($table->offset, $table->limit, $table->sort, $table->order);


	foreach ($event_array as $index => $event_db) {
			$event = new event();
			$event->get_instance_by_id($event_db["event_id"]);
			$prio_icon="transition.png";
			switch ($event->priority) {
					case 0: $prio_icon = "off.png"; 	break;
					case 1: $prio_icon = "error.png";	break;
					case 2: $prio_icon = "error.png";	break;
					case 3:	$prio_icon = "error.png";	break;
					case 4:	$prio_icon = "transition.png"; 	break;
					case 5:	$prio_icon = "active.png"; 	break;
					case 6:	$prio_icon = "idle.png"; 	break;
					case 7:	$prio_icon = "idle.png"; 	break;
			}
			// acknowledged ?
			if ($event->status == 1) {
					$prio_icon="idle.png";
			}
	// check for errors on token
	if (strstr($event->description, "ERROR running token")) {
		$error_token = str_replace("ERROR running token ", "", $event->description);
		$cmd_file = "errors/".$error_token.".cmd";
		$error_file = "errors/".$error_token.".out";

		// get command and error strings
		if ((file_exists($cmd_file)) && (file_exists($error_file))) {
			$oq_cmd = file_get_contents($cmd_file);
			$oq_cmd = str_replace('"','', $oq_cmd);
			$oq_cmd_error = file_get_contents($error_file);
			$oq_cmd_error = str_replace('"','', $oq_cmd_error);
			// set the event to error in any way
			$event_fields=array();
			$event_fields["event_priority"]=1;
			$event->update($event->id, $event_fields);
			$prio_icon = "error.png";
			// set the description
			$event_description = "<a href=\"errors/".$error_token.".out\" title=\"".$oq_cmd_error."\" target=\"_BLANK\">Error</a> running openQRM <a href=\"errors/".$error_token.".cmd\" title=\"".$oq_cmd."\"target=\"_BLANK\">command</a>";
			$event_description .= "<br><a href=\"event-overview.php?action=rerun&token=".$error_token."&event_id=".$event->id."&currenttab=tab0\">Re-Run</a>";
			$event_priority = "<a href=\"errors/".$error_token.".out\" title=\"".$oq_cmd_error."\" target=\"_BLANK\"><img src=\"/openqrm/base/img/".$prio_icon."\"></a>";
		} else {
			// we are currently re-running the token, do not show the links
			$event_description = "Error running openQRM command<br><strong>Currently re-running token $error_token</strong>";
			$event_priority = "<img src=\"/openqrm/base/img/".$prio_icon."\">";
		}
	} else {
		$event_description = $event->description;
		$event_priority = "<img src=\"/openqrm/base/img/".$prio_icon."\">";
	}
	// post which tab we are
	$event_description .= "<input type=\"hidden\" name=\"currenttab\" value=\"tab0\">";

			$arBody[] = array(
					'event_priority' => $event_priority,
					'event_id' => $event_db["event_id"],
					'event_time' => date('Y/m/d H:i:s', $event->time),
					'event_source' => $event->source,
					'event_description' => $event_description,
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
			$table->bottom = array('删除', '确认');
			$table->identifier = 'event_id';
	}
	$table->max = $event_tmp->get_count();

	// set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './event-overview.tpl.php');
	$t->setVar(array(
	'event_table' => $table->get_string(),
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;
}



function errors_only_display() {
	global $OPENQRM_USER;
	global $thisfile;

	$event_tmp = new event();
	$table = new htmlobject_table_builder('event_id', 'DESC', '', '', 'error');

	$arHead = array();
	$arHead['event_priority'] = array();
	$arHead['event_priority']['title'] ='状态';
	$arHead['event_priority']['sortable'] = false;

	$arHead['event_id'] = array();
	$arHead['event_id']['title'] ='编号';

	$arHead['event_time'] = array();
	$arHead['event_time']['title'] ='时间';

	$arHead['event_source'] = array();
	$arHead['event_source']['title'] ='来源';

	$arHead['event_description'] = array();
	$arHead['event_description']['title'] ='描述';
	$arHead['event_description']['sortable'] = false;

	$arBody = array();
	$event_array = $event_tmp->display_error_overview($table->offset, $table->limit, $table->sort, $table->order);

	$event_count=0;
	foreach ($event_array as $index => $event_db) {
		$event = new event();
		$event->get_instance_by_id($event_db["event_id"]);
		$prio_icon="transition.png";
		switch ($event->priority) {
			case 0: $prio_icon = "off.png"; 	break;
			case 1: $prio_icon = "error.png";	break;
			case 2: $prio_icon = "error.png";	break;
			case 3:	$prio_icon = "error.png";	break;
			case 4:	$prio_icon = "transition.png"; 	break;
			case 5:	$prio_icon = "active.png"; 	break;
			case 6:	$prio_icon = "idle.png"; 	break;
			case 7:	$prio_icon = "idle.png"; 	break;
		}
		// check for errors on token
		if (strstr($event->description, "ERROR running token")) {
			$error_token = str_replace("ERROR running token ", "", $event->description);
			$cmd_file = "errors/".$error_token.".cmd";
			$error_file = "errors/".$error_token.".out";

			// get command and error strings
			if ((file_exists($cmd_file)) && (file_exists($error_file))) {
				$oq_cmd = file_get_contents($cmd_file);
				$oq_cmd = str_replace('"','', $oq_cmd);
				$oq_cmd_error = file_get_contents($error_file);
				$oq_cmd_error = str_replace('"','', $oq_cmd_error);
				// set the event to error in any way
				$event_fields=array();
				$event_fields["event_priority"]=1;
				$event->update($event->id, $event_fields);
				$prio_icon = "error.png";
				// set the description
				$event_description = "<a href=\"errors/".$error_token.".out\" title=\"".$oq_cmd_error."\" target=\"_BLANK\">Error</a> running openQRM <a href=\"errors/".$error_token.".cmd\" title=\"".$oq_cmd."\"target=\"_BLANK\">command</a>";
				$event_description .= "<br><a href=\"event-overview.php?action=rerun&token=".$error_token."&event_id=".$event->id."&currenttab=tab1\">Re-Run</a>";
				$event_priority = "<a href=\"errors/".$error_token.".out\" title=\"".$oq_cmd_error."\" target=\"_BLANK\"><img src=\"/openqrm/base/img/".$prio_icon."\"></a>";
			} else {
				// we are currently re-running the token, do not show the links
				$event_description = "Error running openQRM command<br><strong>Currently re-running token $error_token</strong>";
				$event_priority = "<img src=\"/openqrm/base/img/".$prio_icon."\">";
			}
		} else {
			$event_description = $event->description;
			$event_priority = "<img src=\"/openqrm/base/img/".$prio_icon."\">";
		}
		// post which tab we are
		$event_description .= "<input type=\"hidden\" name=\"currenttab\" value=\"tab1\">";

		$arBody[] = array(
			'event_priority' => $event_priority,
			'event_id' => $event_db["event_id"],
			'event_time' => date('Y/m/d H:i:s', $event->time),
			'event_source' => $event->source,
			'event_description' => $event_description,
		);
		$event_count++;

	}

	$table->add_headrow("<input type=\"hidden\" name=\"currenttab\" value=\"tab1\">");
	$table->id = 'Tabelle';
	$table->css = 'htmlobject_table';
	$table->border = 1;
	$table->cellspacing = 0;
	$table->cellpadding = 3;
	$table->form_action = $thisfile;
	$table->head = $arHead;
	$table->body = $arBody;
	$table->autosort = true;
	if ($OPENQRM_USER->role == "administrator") {
		$table->bottom = array('remove', 'acknowledge');
		$table->identifier = 'event_id';
	}
	$table->max = $event_tmp->get_error_count();

		// set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './event-overview.tpl.php');
	$t->setVar(array(
		'event_table' => $table->get_string(),
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;
}


$output = array();
$output[] = array('label' => '所有事件', 'value' => event_display(""));
$output[] = array('label' => '错误事件', 'value' => errors_only_display(""));
echo htmlobject_tabmenu($output);

?>
