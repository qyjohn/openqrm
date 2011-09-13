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

// set vars
$appliance_id = (htmlobject_request('appliance_id') == '') ? @$_REQUEST['identifier'][0] : htmlobject_request('appliance_id');

// handle appliance id not set
if($appliance_id != '') {
	$appliance = new appliance();
	$appliance->get_instance_by_id($appliance_id);

	$ar_request = array(
		'appliance_resources' => isset($_REQUEST['identifier'][0]) ? $_REQUEST['identifier'][0] : $appliance->resources,
		'appliance_name' => (htmlobject_request('appliance_name') != '') ? htmlobject_request('appliance_name') : $appliance->name,
		'appliance_kernelid' => (htmlobject_request('appliance_kernelid') != '') ? htmlobject_request('appliance_kernelid') : $appliance->kernelid,
		'appliance_imageid' => (htmlobject_request('appliance_imageid') != '') ? htmlobject_request('appliance_imageid') : $appliance->imageid,
		'appliance_virtualization' => (htmlobject_request('appliance_virtualization') != '') ? htmlobject_request('appliance_virtualization') : $appliance->virtualization,
		'appliance_cpunumber' => (htmlobject_request('appliance_cpunumber') != '') ? htmlobject_request('appliance_cpunumber') : $appliance->cpunumber,
		'appliance_cpuspeed' => (htmlobject_request('appliance_cpuspeed') != '') ? htmlobject_request('appliance_cpuspeed') : $appliance->cpuspeed,
		'appliance_cpumodel' => (htmlobject_request('appliance_cpumodel') != '') ? htmlobject_request('appliance_cpumodel') : $appliance->cpumodel,
		'appliance_memtotal' => (htmlobject_request('appliance_memtotal') != '') ? htmlobject_request('appliance_memtotal') : $appliance->memtotal,
		'appliance_swaptotal' => (htmlobject_request('appliance_swaptotal') != '') ? htmlobject_request('appliance_swaptotal') : $appliance->swaptotal,
		'appliance_nics' => (htmlobject_request('appliance_nics') != '') ? htmlobject_request('appliance_nics') : $appliance->nics,
		'appliance_capabilities' => (htmlobject_request('appliance_capabilities') != '') ? htmlobject_request('appliance_capabilities') : $appliance->capabilities,
		'appliance_comment' => (htmlobject_request('appliance_comment') != '') ? htmlobject_request('appliance_comment') : $appliance->comment,
	);

	if(isset($_REQUEST['identifier'][0]) == false) {
		$ar_request['appliance_cluster'] = $appliance->cluster;
		$ar_request['appliance_ssi'] = $appliance->ssi;
		$ar_request['appliance_virtual'] = $appliance->virtual;
	} else {
		$ar_request['appliance_cluster'] = (htmlobject_request('appliance_cluster') != '') ? 1 : 0;
		$ar_request['appliance_ssi'] = (htmlobject_request('appliance_ssi') != '') ? 1 : 0;
		$ar_request['appliance_virtual'] = (htmlobject_request('appliance_virtual') != '') ? 1 : 0;
	}

}

function redirect($strMsg, $currenttab = 'tab0', $url = '') {
	global $thisfile;
	if($url == '') {
		$url = 'appliance-index.php?strMsg='.urlencode($strMsg).'&currenttab='.$currenttab;
	}
	// using meta refresh here because the appliance and resourc class pre-sending header output
	echo "<meta http-equiv=\"refresh\" content=\"0; URL=$url\">";
}

if(htmlobject_request('action') != '' && $appliance_id != '') {
	$strMsg = '';
	$openqrm_server = new openqrm_server();

	switch (htmlobject_request('action')) {
		case 'save':
			$error = 0;
			$ar_request['appliance_name'] = strtolower($ar_request['appliance_name']);
			if($ar_request['appliance_name'] != '') {
				if (!preg_match('#^[A-Za-z0-9_-]*$#', $ar_request['appliance_name'])) {
					$strMsg .= 'appliance name must be [A-Za-z0-9_-]<br/>';
					$error = 1;
				}
			} else {
				$strMsg .= "appliance name can not be empty<br/>";
				$error = 1;
			}
						// checks
						$appliance = new appliance();
						$appliance->get_instance_by_id($appliance_id);

						$save_resource_id = $ar_request['appliance_resources'];
						$save_image_id = $ar_request['appliance_imageid'];
						$save_kernel_id = $ar_request['appliance_kernelid'];

						// resource changed ?
						if ($appliance->resources != $save_resource_id) {
						// if resource changed and check that appliance is stopped (do not care about the origin resource)
							if (strcmp($appliance->state, "stopped")) {
								$strMsg .= "Please stop the appliance $appliance_id before changing its resource!<br>Not saving appliance $appliance_id.<br>";
								redirect($strMsg);
								$error = 1;
							}
						}
						// image changed ?
						if ($appliance->imageid != $save_image_id) {
							// if image changed and check that appliance is stopped
							if (strcmp($appliance->state, "stopped")) {
								$strMsg .= "Image of appliance $appliance_id changed.<br>Please restart appliance $appliance_id to apply the change!<br>";
							}
						}
						// kernel changed ?
						if ($appliance->kernelid != $save_kernel_id) {
							// if kernel changed and check that appliance is stopped
							if (strcmp($appliance->state, "stopped")) {
								$strMsg .= "Kernel of appliance $appliance_id changed.<br>Please restart appliance $appliance_id to apply the change!<br>";
							}
						}


			if($error == 0) {
				#$ar_request['appliance_id'] = openqrm_db_get_free_id('appliance_id', $APPLIANCE_INFO_TABLE);
				echo $appliance->update($appliance_id, $ar_request);
				$strMsg .= "Updated appliance ".$appliance_id."<br>";
				redirect($strMsg);
			}
			else { $_REQUEST['strMsg'] = $strMsg; }

		break;
	}
}


function appliance_form() {
	global $OPENQRM_USER, $ar_request, $appliance_id;
	global $thisfile;

	$image = new image();
	$image_list = array();
	$image_list = $image->get_list();
	$image_filtered_list = array();
	// remove the openqrm + idle image from the list
	array_shift($image_list);
	array_shift($image_list);
	// filter out all local images
	foreach($image_list as $image_arr) {
		$image_arr_id = $image_arr['value'];
		$tmpimage = new image();
		$tmpimage->get_instance_by_id($image_arr_id);
		if (!strstr($tmpimage->capabilities, "TYPE=local-server")) {
			$image_filtered_list[] = $image_arr;
		}
	}

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
	$p_appliance = new appliance();
	$p_appliance->get_instance_by_id($appliance_id);
	// set current res_id
	$current_resource_id = $ar_request['appliance_resources'];
	$current_resource = new resource();
	$current_resource->get_instance_by_id($current_resource_id);

	// handle no image available or openqrm server as resource
	if(count($image_list) > 0 || $ar_request['appliance_resources'] == 0) {

		//-------------------------------------- Form
		if ($appliance_id != '') {

			// handle openqrm server as resource
			if ($ar_request['appliance_resources'] != 0) {
				//------------------------------------------------------------ Table
				$table = new htmlobject_db_table('resource_id');
				$arHead = array();
				$arHead['resource_state'] = array();
				$arHead['resource_state']['title'] ='';
				$arHead['resource_state']['sortable'] = false;
				$arHead['resource_icon'] = array();
				$arHead['resource_icon']['title'] ='';
				$arHead['resource_icon']['sortable'] = false;
				$arHead['resource_id'] = array();
				$arHead['resource_id']['title'] ='ID';
				$arHead['resource_name'] = array();
				$arHead['resource_name']['title'] ='Name';
				$arHead['resource_mac'] = array();
				$arHead['resource_mac']['title'] ='Mac';
				$arHead['resource_ip'] = array();
				$arHead['resource_ip']['title'] ='Ip';
				$arHead['resource_vtype'] = array();
				$arHead['resource_vtype']['title'] ='Type';

				$auto_resource_icon="/openqrm/base/img/resource.png";
				$auto_state_icon="/openqrm/base/img/active.png";

				$arBody = array();
				// add auto-select resource to table
				$arBody[] = array(
					'resource_state' => "<img src=$auto_state_icon>",
					'resource_icon' => "<img width=24 height=24 src=$auto_resource_icon>",
					'resource_id' => '-1',
					'resource_name' => "auto-select resource",
					'resource_mac' => "x:x:x:x:x:x",
					'resource_ip' => "0.0.0.0",
					'resource_vtype' => "auto",
				);
				// build the disabled array
				$identifier_disabled_local_res_arr = array();
				$identifier_disabled_local_res_arr[] = -1; // auto-select
				$identifier_disabled_local_res_arr[] = 0; // openqrm
				$identifier_disabled_non_local_res_arr = array();
				$identifier_disabled_non_local_res_arr[] = 0; // openqrm

				$resource_tmp = new resource();
				$resource_array = $resource_tmp->display_overview($table->offset, $table->limit, $table->sort, $table->order);
				foreach ($resource_array as $index => $resource_db) {
					$resource = new resource();
					$resource->get_instance_by_id($resource_db["resource_id"]);
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
					// add to disabled arr if not the current resource
					if ($resource->id != $current_resource_id) {
						$identifier_disabled_local_res_arr[] = $resource->id;
					}
					if (strstr($resource->capabilities, "TYPE=local-server")) {
						$identifier_disabled_non_local_res_arr[] = $resource->id;
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
				$table->head = $arHead;
				$table->body = $arBody;
				if ($OPENQRM_USER->role == "administrator") {
					$table->identifier = 'resource_id';
					$table->identifier_type = 'radio';
					$table->identifier_checked = array($ar_request['appliance_resources']);
					
					// is local-server resource or non-local-resource ?
					if (strstr($current_resource->capabilities, "TYPE=local-server")) {
						$table->identifier_disabled = $identifier_disabled_local_res_arr;
					} else {
						$table->identifier_disabled = $identifier_disabled_non_local_res_arr;
					}
				}
				$table->max = count($resource_array) +1;
				$strTable = '<h3>Resource List</h3>'.$table->get_string();

				// check resource type, local ?
				$check_resource = new resource();
				$check_resource->get_instance_by_id($current_resource_id);
				if (strstr($check_resource->capabilities, "TYPE=local-server")) {
					// integrated by local-server
					$local_kernel = new kernel();
					$local_kernel_name = "resource".$current_resource_id;
					$local_kernel->get_instance_by_name($local_kernel_name);
					$local_image = new image();
					$local_image_name = "resource".$current_resource_id;
					$local_image->get_instance_by_name($local_image_name);
					$kernelid = htmlobject_input('appliance_kernelid', array("value" => $local_kernel->id, "label" => "$local_kernel_name"), 'hidden');
					$image = htmlobject_input('appliance_imageid', array("value" => $local_image->id, "label" => "$local_image_name"), 'hidden');

				} else {
					// network-deployment
					$kernelid = htmlobject_select('appliance_kernelid', $kernel_filtered_list, 'Kernel', array($ar_request['appliance_kernelid']));
					$image = htmlobject_select('appliance_imageid', $image_filtered_list, 'Image', array($ar_request['appliance_imageid']));
				}

			}
			// set inputs for openqrm server as resource
			else {
				$strTable = htmlobject_input('identifier[]', array("value" => 0, "label" => ''), 'hidden');
				$kernelid = htmlobject_input('appliance_kernelid', array("value" => 0, "label" => ''), 'hidden');
				$image = htmlobject_input('appliance_imageid', array("value" => 0, "label" => ''), 'hidden');
			}

			// set template
			$t = new Template_PHPLIB();
			$t->debug = false;
			$t->setFile('tplfile', './' . 'appliance-edit-tpl.php');
			$t->setVar(array(
				'thisfile' => $thisfile,
				'step_2' => htmlobject_input('step_2', array("value" => true, "label" => ''), 'hidden'),
				'identifier' => '',
				'currentab' => htmlobject_input('currenttab', array("value" => 'tab2', "label" => ''), 'hidden'),
				'lang_requirements' => '<h3>Requirements</h3>',
				'appliance_kernelid' => $kernelid,
				'appliance_imageid' => $image,
				'appliance_virtualization' => htmlobject_select('appliance_virtualization', $virtualization_list, 'Resource type', array($ar_request['appliance_virtualization'])),
				'appliance_name' => htmlobject_input('appliance_name', array("value" => $ar_request['appliance_name'], "label" => 'Name'), 'text', 20),
				'appliance_cpunumber' => htmlobject_select('appliance_cpunumber', $available_cpunumber, 'CPUs', array($p_appliance->cpunumber)),
				'appliance_cpuspeed' => htmlobject_select('appliance_cpuspeed', $available_cpuspeed, 'CPU-speed', array($p_appliance->cpuspeed)),
				'appliance_cpumodel' => htmlobject_select('appliance_cpumodel', $available_cpumodel, 'CPU-model', array($p_appliance->cpumodel)),
				'appliance_memtotal' => htmlobject_select('appliance_memtotal', $available_memtotal, 'Memory', array($p_appliance->memtotal)),
				'appliance_swaptotal' => htmlobject_select('appliance_swaptotal', $available_swaptotal, 'Swap', array($p_appliance->swaptotal)),
				'appliance_nics' => htmlobject_select('appliance_nics', $available_nics, 'Network Cards', array($p_appliance->nics)),
				'appliance_capabilities' => htmlobject_input('appliance_capabilities', array("value" => $ar_request['appliance_capabilities'], "label" => 'Capabilities'), 'text', 255),
				'appliance_comment' => htmlobject_textarea('appliance_comment', array("value" => $ar_request['appliance_comment'], "label" => 'Comment')),
				'appliance_cluster' => htmlobject_input('appliance_cluster', array("value" => 1, "label" => 'Cluster'), 'checkbox', ($ar_request['appliance_cluster'] == 0) ? false : true),
				'appliance_ssi' => htmlobject_input('appliance_ssi', array("value" => 1, "label" => 'SSI'), 'checkbox', ($ar_request['appliance_ssi'] == 0) ? false : true),
				'appliance_virtual' => htmlobject_input('appliance_virtual', array("value" => 1, "label" => 'Virtual'), 'checkbox', ($ar_request['appliance_virtual'] == 0) ? false : true),
				'submit_save' => htmlobject_input('action', array("value" => 'save', "label" => 'save'), 'submit'),
				'lang_table' => '',
				'appliance_id' => htmlobject_input('appliance_id', array("value" => $appliance_id, "label" => ''), 'hidden'),
				'table' =>  $strTable,
			));
			$disp = $t->parse('out', 'tplfile');
		}
	}
	// handle no image available
	else {
		$disp = '<center>';
		$disp .= '<b>No Image available</b>';
		$disp .= '<br><br>';
		$disp .= '<a href="../image/image-new.php?currenttab=tab1">Image</a>';
		$disp .= '</center>';
		$disp .= '<br><br>';
	}

	return "<h1>编辑器件</h1>". $disp;
}

$output = array();
$output[] = array('label' => '器件列表', 'target' => 'appliance-index.php');
$output[] = array('label' => '创建器件', 'target' => 'appliance-new.php');

// handle appliance id not set
if($appliance_id != '') {
	$output[] = array('label' => '编辑器件', 'value' => appliance_form());
} else {
	$_REQUEST['strMsg'] = 'Appliance ID not set';
	$_REQUEST['currenttab'] = 'tab2';
	$output[] = array('label' => '编辑器件', 'value' => '');
}

?>
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
<link rel="stylesheet" type="text/css" href="appliance.css" />
<?php
echo htmlobject_tabmenu($output);
?>
