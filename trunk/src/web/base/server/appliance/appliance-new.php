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
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/include/htmlobject.inc.php";

if(strtolower(OPENQRM_USER_ROLE_NAME) != 'administrator') {
	echo 'Access denied';
	exit;
}

// set vars from request
$ar_request = array(
	'appliance_resources' => htmlobject_request('appliance_resources'),
	'appliance_name' => strtolower(htmlobject_request('appliance_name')),
	'appliance_kernelid' => htmlobject_request('appliance_kernelid'),
	'appliance_imageid' => htmlobject_request('appliance_imageid'),
	'appliance_virtualization' => htmlobject_request('appliance_virtualization'),
	'appliance_cpunumber' => htmlobject_request('appliance_cpunumber'),
	'appliance_cpuspeed' => htmlobject_request('appliance_cpuspeed'),
	'appliance_cpumodel' => htmlobject_request('appliance_cpumodel'),
	'appliance_memtotal' => htmlobject_request('appliance_memtotal'),
	'appliance_swaptotal' => htmlobject_request('appliance_swaptotal'),
	'appliance_nics' => htmlobject_request('appliance_nics'),
	'appliance_capabilities' => htmlobject_request('appliance_capabilities'),
	'appliance_comment' => htmlobject_request('appliance_comment'),
	'appliance_id' => '',
	'appliance_cluster' => (htmlobject_request('appliance_cluster') == '') ? 0 : 1,
	'appliance_ssi' => (htmlobject_request('appliance_ssi') == '') ? 0 : 1,
	'appliance_highavailable' => (htmlobject_request('appliance_highavailable') == '') ? 0 : 1,
	'appliance_virtual' => (htmlobject_request('appliance_virtual') == '') ? 0 : 1,
);

function redirect($strMsg, $currenttab = 'tab0', $url = '') {
	global $thisfile;
	if($url == '') {
		$url = 'appliance-index.php?strMsg='.urlencode($strMsg).'&currenttab='.$currenttab;
	}
	//	using meta refresh here because the appliance and resourc class pre-sending header output
	echo "<meta http-equiv=\"refresh\" content=\"0; URL=$url\">";
}


$step='';
if(htmlobject_request('action') != '') {
	$strMsg = '';
	$openqrm_server = new openqrm_server();
	$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
	global $OPENQRM_SERVER_IP_ADDRESS;


	switch (htmlobject_request('action')) {

		// from step1
		case 'select':
			if (isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $id) {
					$resource_id = $id;
					$step=2;
					break;
				}
			}
			break;

		// from step2
		case 'set':
			if($ar_request['appliance_imageid'] != '') {
				$image_id = htmlobject_request('appliance_imageid');
				$resource_id = htmlobject_request('appliance_resources');
				$step=3;
				break;
			}
			break;


		case 'save':
			$error = 0;

			if($ar_request['appliance_name'] != '') {
				if (!preg_match('#^[A-Za-z0-9_-]*$#', $ar_request['appliance_name'])) {
					$strMsg .= 'Appliance name must be [A-Za-z0-9_-]<br/>';
					$error = 1;
				}
				// check that name is unique
				$appliance_name_check = new appliance();
				$appliance_name_check->get_instance_by_name($ar_request['appliance_name']);
				if ($appliance_name_check->id > 0) {
					$strMsg .= "Appliance name must be unique!<br/>";
					$error = 1;
				}

			} else {
				$strMsg .= "Appliance name can not be empty<br/>";
				$error = 1;
			}
			if($error == 0) {
				$ar_request['appliance_id'] = openqrm_db_get_free_id('appliance_id', $APPLIANCE_INFO_TABLE);
				$appliance = new appliance();
				$appliance->add($ar_request);
				$strMsg .= 'Added new appliance';
				redirect($strMsg);
			}
			else { $_REQUEST['strMsg'] = $strMsg; }

		break;
	}
}





// select resource
function appliance_form_step1() {
	global $OPENQRM_USER, $ar_request;
	global $thisfile;

	$table = new htmlobject_db_table('resource_id');
	$table->add_headrow(htmlobject_input('currenttab', array("value" => 'tab1', "label" => ''), 'hidden'));

	$arHead = array();
	$arHead['resource_state'] = array();
	$arHead['resource_state']['title'] ='';
	$arHead['resource_state']['sortable'] = false;
	$arHead['resource_icon'] = array();
	$arHead['resource_icon']['title'] ='';
	$arHead['resource_icon']['sortable'] = false;
	$arHead['resource_id'] = array();
	$arHead['resource_id']['title'] ='编号';
	$arHead['resource_name'] = array();
	$arHead['resource_name']['title'] ='名称';
	$arHead['resource_mac'] = array();
	$arHead['resource_mac']['title'] ='Mac地址';
	$arHead['resource_ip'] = array();
	$arHead['resource_ip']['title'] ='IP地址';
	$arHead['resource_vtype'] = array();
	$arHead['resource_vtype']['title'] ='类别';

	$resource_count=0;

	$auto_resource_icon="/openqrm/base/img/resource.png";
	$auto_state_icon="/openqrm/base/img/active.png";

	$arBody = array();
	$arBody[] = array(
		'resource_state' => "<img src=$auto_state_icon>",
		'resource_icon' => "<img width=24 height=24 src=$auto_resource_icon>",
		'resource_id' => '-1',
		'resource_name' => "auto-select resource",
		'resource_mac' => "x:x:x:x:x:x",
		'resource_ip' => "0.0.0.0",
		'resource_vtype' => "auto",
	);

	$resource_tmp = new resource();
	$resource_array = $resource_tmp->display_overview($table->offset, $table->limit, $table->sort, $table->order);
	foreach ($resource_array as $index => $resource_db) {
		$resource = new resource();
		$resource->get_instance_by_id($resource_db["resource_id"]);

		$resource_count++;
		$resource_icon_default="/openqrm/base/img/resource.png";
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
			'resource_name' => $resource->hostname,
			'resource_mac' => $resource->mac,
			'resource_ip' => $resource->ip,
			'resource_vtype' => $resource_type_info,
		);
	}

	$table->id = 'Tabelle';
	$table->css = 'htmlobject_table';
	$table->border = 1;
	$table->cellspacing = 0;
	$table->cellpadding = 3;
	$table->form_action = "appliance-new.php";
	$table->head = $arHead;
	$table->body = $arBody;
	if ($OPENQRM_USER->role == "administrator") {
		$table->bottom = array('select');
		$table->identifier = 'resource_id';
		$table->identifier_type = 'radio';
	}
	$table->max = count($resource_array) +1;

	//------------------------------------------------------------ set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './' . 'appliance-new1-tpl.php');
	$t->setVar(array(
		'thisfile' => $thisfile,
		'currentab' => htmlobject_input('currenttab', array("value" => 'tab1', "label" => ''), 'hidden'),
		'resource_table' => $table->get_string(),
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;
}






// select image
function appliance_form_step2($resource_id) {
	global $OPENQRM_USER, $ar_request;
	global $thisfile;

	$image = new image();
	$image_list = array();
	$image_list = $image->get_list();
	$image_filtered_list = array();
	// remove the openqrm + idle image from the list
	//print_r($image_list);
	array_shift($image_list);
	array_shift($image_list);
	// filter out all local images
	foreach($image_list as $image_arr) {
		$image_arr_id = $image_arr['value'];
		$tmpimage = new image();
		$tmpimage->get_instance_by_id($image_arr_id);
		if (!strstr($tmpimage->capabilities, "TYPE=local-server")) {
			$tmp_image_name = $image_arr['label'];
			$tmpdeployment = new deployment();
			$tmpdeployment->get_instance_by_type($tmpimage->type);
			$image_name_plus_type = $tmp_image_name.' - '.$tmpdeployment->description;
			$image_arr['label'] = $image_name_plus_type;

			$image_filtered_list[] = $image_arr;
		}
	}

	if($resource_id == 0) {
		// build local installed image
		$local_image_list = array();
		$local_image_list[] = array("value" => '0', "label" => 'Local openQRM Installation');
		$image = htmlobject_select('appliance_imageid', $local_image_list, 'Image', array(0));

	} else {

		$check_resource = new resource();
		$check_resource->get_instance_by_id($resource_id);
		if (strstr($check_resource->capabilities, "TYPE=local-server")) {
			// integrated by local-server
			$local_image = new image();
			$local_image_name = "resource".$resource_id;
			$local_image->get_instance_by_name($local_image_name);
			// build local installed image
			$local_image_list = array();
			$local_image_list[] = array("value" => $local_image->id, "label" => 'Local OS Installation');
			$image = htmlobject_select('appliance_imageid', $local_image_list, 'Image', array(0));

		} else {
			// network deployment, filtered out local kernel + image
			$image = htmlobject_select('appliance_imageid', $image_filtered_list, 'Image', array($ar_request['appliance_imageid']));
		}
	}


	if(count($image_list) > 0 || $resource_id == 0) {
		//------------------------------------------------------------ set template
		$t = new Template_PHPLIB();
		$t->debug = false;
		$t->setFile('tplfile', './' . 'appliance-new2-tpl.php');
		$t->setVar(array(
			'thisfile' => $thisfile,
			'currentab' => htmlobject_input('currenttab', array("value" => 'tab1', "label" => ''), 'hidden'),
			'appliance_resources' => htmlobject_input('appliance_resources', array("value" => $resource_id, "label" => ''), 'hidden'),
			'appliance_imageid' => $image,
			'submit_set' => htmlobject_input('action', array("value" => 'set', "label" => 'set'), 'submit'),
		));
		$disp =  $t->parse('out', 'tplfile');

	} else {
		$disp = '<center>';
		$disp .= '<b>没有可用的映像</b>';
		$disp .= '<br><br>';
		$disp .= '<a href="../image/image-new.php?currenttab=tab1">管理映像</a>';
		$disp .= '</center>';
		$disp .= '<br><br>';
	}
	return $disp;
}
	



// set requirements and kernel
function appliance_form_step3($resource_id, $image_id) {
	global $OPENQRM_USER, $ar_request;
	global $thisfile;
	global $BaseDir;

	$kernel = new kernel();
	$kernel_list = array();
	$kernel_list = $kernel->get_list();
	$kernel_filtered_list = array();
	// remove the openqrm kernelfrom the list
	array_shift($kernel_list);
	// filter out all local kernels
	foreach($kernel_list as $kernel_arr) {
		$kernel_arr_id = $kernel_arr['value'];
		$tmpkernel = new kernel();
		$tmpkernel->get_instance_by_id($kernel_arr_id);
		if (!strstr($tmpkernel->capabilities, "TYPE=local-server")) {
			$kernel_filtered_list[] = $kernel_arr;
		}
	}

	$virtualization = new virtualization();
	$virtualization_list = array();
	$virtualization_list = $virtualization->get_list();
	// preselect the virt type
	$resource_type = 1;
	$resource = new resource();
	$resource->get_instance_by_id($resource_id);
	if ($resource->vhostid != $resource->id) {
		$resource_type = $resource->vtype;
	}



	// get list of available resource parameters
	$resource_p = new resource();
	$resource_p_array = $resource_p->get_list();
	// remove openQRM resource
	array_shift($resource_p_array);
	// gather all available values in arrays
	$available_cpuspeed_uniq = array();
	$available_cpuspeed = array();
	$available_cpuspeed[] = array("value" => "0", "label" => "any");
	$available_cpunumber_uniq = array();
	$available_cpunumber = array();
	$available_cpunumber[] = array("value" => "0", "label" => "any");
	$available_cpumodel_uniq = array();
	$available_cpumodel = array();
	$available_cpumodel[] = array("value" => "0", "label" => "any");
	$available_memtotal_uniq = array();
	$available_memtotal = array();
	$available_memtotal[] = array("value" => "0", "label" => "any");
	$available_swaptotal_uniq = array();
	$available_swaptotal = array();
	$available_swaptotal[] = array("value" => "0", "label" => "any");
	$available_nics_uniq = array();
	$available_nics = array();
	$available_nics[] = array("value" => "0", "label" => "any");
	foreach($resource_p_array as $res) {
		$res_id = $res['resource_id'];
		$tres = new resource();
		$tres->get_instance_by_id($res_id);
		if (!in_array($tres->cpuspeed, $available_cpuspeed_uniq)) {
			$available_cpuspeed[] = array("value" => $tres->cpuspeed, "label" => $tres->cpuspeed);
			$available_cpuspeed_uniq[] .= $tres->cpuspeed;
		}
		if (!in_array($tres->cpunumber, $available_cpunumber_uniq)) {
			$available_cpunumber[] = array("value" => $tres->cpunumber, "label" => $tres->cpunumber);
			$available_cpunumber_uniq[] .= $tres->cpunumber;
		}
		if (!in_array($tres->cpumodel, $available_cpumodel_uniq)) {
			$available_cpumodel[] = array("value" => $tres->cpumodel, "label" => $tres->cpumodel);
			$available_cpumodel_uniq[] .= $tres->cpumodel;
		}
		if (!in_array($tres->memtotal, $available_memtotal_uniq)) {
			$available_memtotal[] = array("value" => $tres->memtotal, "label" => $tres->memtotal);
			$available_memtotal_uniq[] .= $tres->memtotal;
		}
		if (!in_array($tres->swaptotal, $available_swaptotal_uniq)) {
			$available_swaptotal[] = array("value" => $tres->swaptotal, "label" => $tres->swaptotal);
			$available_swaptotal_uniq[] .= $tres->swaptotal;
		}
		if (!in_array($tres->nics, $available_nics_uniq)) {
			$available_nics[] = array("value" => $tres->nics, "label" => $tres->nics);
			$available_nics_uniq[] .= $tres->nics;
		}
	}


	if($resource_id == 0) {
		$kernelid = htmlobject_input('appliance_kernelid', array("value" => '0', "label" => ''), 'hidden');

	} else {

		$check_resource = new resource();
		$check_resource->get_instance_by_id($resource_id);
		if (strstr($check_resource->capabilities, "TYPE=local-server")) {
			// integrated by local-server
			$local_kernel = new kernel();
			$local_kernel_name = "resource".$resource_id;
			$local_kernel->get_instance_by_name($local_kernel_name);
			$kernelid = htmlobject_input('appliance_kernelid', array("value" => $local_kernel->id, "label" => "$local_kernel_name"), 'hidden');

		} else {

			// check image-identifier hook if the iamge provides the capability to specify a network-boot kernel
			$image = new image();
			$image->get_instance_by_id($image_id);
			$storage = new storage();
			$storage->get_instance_by_id($image->storageid);
			$deployment = new deployment();
			$deployment->get_instance_by_id($storage->type);
			$rootdevice_identifier_hook="";
			$rootdevice_identifier_hook = "$BaseDir/boot-service/image.$deployment->type.php";
			// require once
			if (file_exists($rootdevice_identifier_hook)) {
				require_once "$rootdevice_identifier_hook";
				$is_network_deployment = get_is_network_deployment();
				if ($is_network_deployment) {
					// network deployment, filtered out local kernel
					$kernelid = htmlobject_select('appliance_kernelid', $kernel_filtered_list, 'Kernel', array($ar_request['appliance_kernelid']));
				} else {
					$kernelid = htmlobject_input('appliance_kernelid', array("value" => '1', "label" => 'default'), 'hidden');
				}

			} else {
				$kernelid = htmlobject_input('appliance_kernelid', array("value" => '1', "label" => 'default'), 'hidden');
			}
		}
	}


	//------------------------------------------------------------ set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './' . 'appliance-new3-tpl.php');
	$t->setVar(array(
		'thisfile' => $thisfile,
		'currentab' => htmlobject_input('currenttab', array("value" => 'tab1', "label" => ''), 'hidden'),
		'appliance_resources' => htmlobject_input('appliance_resources', array("value" => $resource_id, "label" => ''), 'hidden'),
		'appliance_imageid' => htmlobject_input('appliance_imageid', array("value" => $image_id, "label" => ''), 'hidden'),
		'appliance_kernelid' => $kernelid,
		'appliance_virtualization' => htmlobject_select('appliance_virtualization', $virtualization_list, 'Resource', array($resource_type)),
		'appliance_name' => htmlobject_input('appliance_name', array("value" => $ar_request['appliance_name'], "label" => 'Name'), 'text', 20),
		'appliance_cpunumber' => htmlobject_select('appliance_cpunumber', $available_cpunumber, 'CPUs'),
		'appliance_cpuspeed' => htmlobject_select('appliance_cpuspeed', $available_cpuspeed, 'CPU-speed'),
		'appliance_cpumodel' => htmlobject_select('appliance_cpumodel', $available_cpumodel, 'CPU-model'),
		'appliance_memtotal' => htmlobject_select('appliance_memtotal', $available_memtotal, 'Memory'),
		'appliance_swaptotal' => htmlobject_select('appliance_swaptotal', $available_swaptotal, 'Swap'),
		'appliance_nics' => htmlobject_select('appliance_nics', $available_nics, 'Network Cards'),
		'appliance_capabilities' => htmlobject_input('appliance_capabilities', array("value" => $ar_request['appliance_capabilities'], "label" => 'Capabilities'), 'text', 255),
		'appliance_comment' => htmlobject_textarea('appliance_comment', array("value" => $ar_request['appliance_comment'], "label" => 'Comment')),
		'appliance_cluster' => htmlobject_input('appliance_cluster', array("value" => 1, "label" => 'Cluster'), 'checkbox', ($ar_request['appliance_cluster'] == 0) ? false : true),
		'appliance_ssi' => htmlobject_input('appliance_ssi', array("value" => 1, "label" => 'SSI'), 'checkbox', ($ar_request['appliance_ssi'] == '') ? false : true),
		'appliance_highavailable' => htmlobject_input('appliance_highavailable', array("value" => 1, "label" => 'Highavailable'), 'checkbox', ($ar_request['appliance_highavailable'] == 0) ? false : true),
		'appliance_virtual' => htmlobject_input('appliance_virtual', array("value" => 1, "label" => 'Virtual'), 'checkbox', ($ar_request['appliance_virtual'] == 0) ? false : true),
		'submit_save' => htmlobject_input('action', array("value" => 'save', "label" => 'save'), 'submit'),
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;
}






$output = array();
$output[] = array('label' => '器件列表', 'target' => 'appliance-index.php');


switch ($step) {
	case 1:
		$output[] = array('label' => '创建器件', 'value' => appliance_form_step1());
		break;
	case 2:
		$output[] = array('label' => '创建器件', 'value' => appliance_form_step2($resource_id));
		break;
	case 3:
		$output[] = array('label' => '创建器件', 'value' => appliance_form_step3($resource_id, $image_id));
		break;
	default:
		$output[] = array('label' => '创建器件', 'value' => appliance_form_step1());
		break;
}



?>
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
<link rel="stylesheet" type="text/css" href="appliance.css" />
<?php
echo htmlobject_tabmenu($output);
?>
