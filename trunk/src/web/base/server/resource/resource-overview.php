<!doctype html>
<html lang="en">
<head>
<title>openQRM</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></meta>
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
<link rel="stylesheet" type="text/css" href="resource.css" />
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
    Copyright 2011, Qingye Jiang (John) <qjiang@ieee.org>
*/

$thisfile = basename($_SERVER['PHP_SELF']);
$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/include/htmlobject.inc.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/include/openqrm-server-config.php";
global $OPENQRM_SERVER_BASE_DIR;
global $OPENQRM_EXEC_PORT;
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;


function redirect($strMsg, $currenttab = 'tab0', $url = '') {
	global $thisfile;
	if($url == '') {
		$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab='.$currenttab;
	}
	//	using meta refresh here because the resource and resourc class pre-sending header output
	echo "<meta http-equiv=\"refresh\" content=\"0; URL=$url\">";
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


if(htmlobject_request('action') != '' && $OPENQRM_USER->role == "administrator") {
	$strMsg = '';
	$return_msg = '';
	if(isset($_REQUEST['identifier'])) {
		switch (htmlobject_request('action')) {
			case '重启':
				show_progressbar();
				foreach($_REQUEST['identifier'] as $id) {
					if($id != 0) {
						$resource = new resource();
						$resource->get_instance_by_id($id);
						$ip = $resource->ip;
						$return_msg .= $resource->send_command("$ip", "reboot");
						$strMsg .= "Rebooted resource $id <br>";
						// set state to transition
						$resource_fields=array();
						$resource_fields["resource_state"]="transition";
						$resource->update_info($id, $resource_fields);
					}
				}
				sleep(1);
				redirect($strMsg);
				break;

			case '关机':
				show_progressbar();
				foreach($_REQUEST['identifier'] as $id) {
					if($id != 0) {
						$resource = new resource();
						$resource->get_instance_by_id($id);
						$ip = $resource->ip;
						$return_msg .= $resource->send_command("$ip", "halt");
						$strMsg .= "Shutdown resource $id <br>";
						// set state to transition
						$resource_fields=array();
						$resource_fields["resource_state"]="off";
						$resource->update_info($id, $resource_fields);
					}
				}
				sleep(1);
				redirect($strMsg);
				break;

			case '删除':
				show_progressbar();
				$return_msg = '';
				foreach($_REQUEST['identifier'] as $id) {
					if($id != 0) {
						// check that resource is not still used by an appliance
						$resource_is_used_by_appliance = "";
						$remove_error = 0;
						$appliance = new appliance();
						$appliance_id_list = $appliance->get_all_ids();
						foreach($appliance_id_list as $appliance_list) {
							$appliance_id = $appliance_list['appliance_id'];
							$app_resource_remove_check = new appliance();
							$app_resource_remove_check->get_instance_by_id($appliance_id);
							if ($app_resource_remove_check->resources == $id) {
								$resource_is_used_by_appliance .= $appliance_id." ";
								$remove_error = 1;
							}
						}
						if ($remove_error == 1) {
							$strMsg .= "Resource id ".$id." is used by appliance(s): ".$resource_is_used_by_appliance." <br>";
							$strMsg .= "Not removing resource id ".$id." !<br>";
							continue;
						}

						// here we remove the resource
						$resource = new resource();
						$resource->get_instance_by_id($id);
						$mac = $resource->mac;
						$return_msg .= $resource->remove($id, $mac);
						$strMsg .= "Removed resource $id <br>";
					}
				}
				sleep(1);
				redirect($strMsg);
				break;



		}

	} //identifier
	#else { redirect('Please select a resource'); }
}


function resource_display() {
	global $OPENQRM_USER;
	global $thisfile;

	$resource_tmp = new resource();
	$table = new htmlobject_db_table('resource_id');

	$arHead = array();
	$arHead['resource_state'] = array();
	$arHead['resource_state']['title'] ='';

	$arHead['resource_icon'] = array();
	$arHead['resource_icon']['title'] ='';

	$arHead['resource_id'] = array();
	$arHead['resource_id']['title'] ='编号';

	$arHead['resource_hostname'] = array();
	$arHead['resource_hostname']['title'] ='名称';

	$arHead['resource_mac'] = array();
	$arHead['resource_mac']['title'] ='Mac地址';

	$arHead['resource_ip'] = array();
	$arHead['resource_ip']['title'] ='IP地址';

	$arHead['resource_type'] = array();
	$arHead['resource_type']['title'] ='类别';

	$arHead['resource_memtotal'] = array();
	$arHead['resource_memtotal']['title'] ='内存';

	$arHead['resource_load'] = array();
	$arHead['resource_load']['title'] ='负载';

	$arBody = array();
	$resource_array = $resource_tmp->display_overview($table->offset, $table->limit, $table->sort, $table->order);

	foreach ($resource_array as $index => $resource_db) {
		// prepare the values for the array
		$resource = new resource();
		$resource->get_instance_by_id($resource_db["resource_id"]);
		$res_id = $resource->id;
		$mem_total = $resource_db['resource_memtotal'];
		$mem_used = $resource_db['resource_memused'];
		$mem = "$mem_used/$mem_total";
		$swap_total = $resource_db['resource_swaptotal'];
		$swap_used = $resource_db['resource_swapused'];
		$swap = "$swap_used/$swap_total";
		if ($resource->id == 0) {
			$resource_icon_default="/openqrm/base/img/logo.png";
			$resource_type = "openQRM";
			$resource_mac = "x:x:x:x:x:x";
		} else {
			$resource_mac = $resource_db["resource_mac"];
			$resource_icon_default="/openqrm/base/img/resource.png";
			// the resource_type
			if ((strlen($resource->vtype)) && (!strstr($resource->vtype, "NULL"))){
				// find out what should be preselected
				$virtualization = new virtualization();
				$virtualization->get_instance_by_id($resource->vtype);
				if ($resource->id == $resource->vhostid) {
					// physical system
					$resource_type = "<nobr>".$virtualization->name."</nobr>";
				} else {
					// vm
					$resource_type = "<nobr>".$virtualization->name." on Res. ".$resource->vhostid."</nobr>";
				}
			} else {
				$resource_type = "Unknown";
			}

		}
		$state_icon="/openqrm/base/img/$resource->state.png";
		// idle ?
		if (("$resource->imageid" == "1") && ("$resource->state" == "active")) {
			$state_icon="/openqrm/base/img/idle.png";
		}
		if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/".$state_icon)) {
			$state_icon="/openqrm/base/img/unknown.png";
		}

		$arBody[] = array(
			'resource_state' => "<img src=$state_icon>",
			'resource_icon' => "<img width=24 height=24 src=$resource_icon_default>",
			'resource_id' => $resource_db["resource_id"],
			'resource_hostname' => $resource_db["resource_hostname"],
			'resource_mac' => $resource_mac,
			'resource_ip' => $resource_db["resource_ip"],
			'resource_type' => $resource_type,
			'resource_memtotal' => $mem,
			'resource_load' => $resource_db["resource_load"],
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
		$table->bottom = array('重启', '关机', '删除');
		$table->identifier = 'resource_id';
		$table->identifier_disabled = array(0);
	}
	$table->max = $resource_tmp->get_count('all') + 1; // adding openqrmserver

	// set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './resource-overview.tpl.php');
	$t->setVar(array(
		'resource_table' => $table->get_string(),
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;
}



function resource_create() {

	$virtualization = new virtualization();
	$virtualization_list = array();
	$virtualization_list = $virtualization->get_list();
	$virtualization_link_section = "";
	// filter out the virtualization hosts
	foreach ($virtualization_list as $id => $virt) {
		$virtualization_id = $virt['value'];
		$available_virtualization = new virtualization();
		$available_virtualization->get_instance_by_id($virtualization_id);
		if (strstr($available_virtualization->type, "-vm")) {
			$virtualization_plugin_name = str_replace("-vm", "", $available_virtualization->type);
			$virtualization_name = substr($available_virtualization->name, 0, -2);
			if (strrpos($available_virtualization->type, "-storage")) {
				$virtualization_link_section .= "<a href='/openqrm/base/plugins/".$virtualization_plugin_name."/".$virtualization_plugin_name."-vm-manager.php' style='text-decoration: none'><img title='Create a ".$virtualization_name."Virtual Machine' alt='Create a ".$virtualization_name."Virtual Machine' src='/openqrm/base/plugins/".$virtualization_plugin_name."/img/plugin.png' border=0> ".$virtualization_name."Virtual Machine</a><br>";
			} else {
				$virtualization_link_section .= "<a href='/openqrm/base/plugins/".$virtualization_plugin_name."/".$virtualization_plugin_name."-manager.php' style='text-decoration: none'><img title='Create a ".$virtualization_name."Virtual Machine' alt='Create a ".$virtualization_name."Virtual Machine' src='/openqrm/base/plugins/".$virtualization_plugin_name."/img/plugin.png' border=0> ".$virtualization_name."Virtual Machine</a><br>";
			}
		}
	}
	if (!strlen($virtualization_link_section)) {
		$virtualization_link_section = "请启用并启动至少一个虚拟化插件。";
	}


	// local-server plugin enabled
	if (file_exists($_SERVER["DOCUMENT_ROOT"]."/openqrm/base/plugins/local-server/local-server-about.php")) {
		$local_server_plugin_link = "<a href='/openqrm/base/plugins/local-server/local-server-about.php' style='text-decoration: none'><img title='Integrate an existing local installed Server' alt='Integrate an existing local installed Server' src='/openqrm/base/plugins/local-server/img/plugin.png' border=0> Integrate an existing local installed Server</a>";
	} else {
		$local_server_plugin_link = "请启用并启动local-server插件。";
	}


	// set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './resource-create.tpl.php');
	$t->setVar(array(
		'resource_new' => "<a href='resource-new.php' style='text-decoration: none'><img title='Manual create (un-managed) resource' alt='Manual create (un-managed) resource' src='/openqrm/base/img/resource.png' border=0> 手工添加不受管理的计算资源 </a>",
		'resource_local' => $local_server_plugin_link,
		'resource_virtual' => $virtualization_link_section,

	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;

}



$output = array();
$output[] = array('label' => '资源列表', 'value' => resource_display());
if($OPENQRM_USER->role == "administrator") {
	$output[] = array('label' => '创建资源', 'value' => resource_create());
}

echo htmlobject_tabmenu($output);

?>

