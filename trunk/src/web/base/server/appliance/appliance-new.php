<?php
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

function redirect($strMsg, $currenttab = 'tab0', $url = '') {
	global $thisfile;
	if($url == '') {
		$url = 'appliance-index.php?strMsg='.urlencode($strMsg).'&currenttab='.$currenttab;
	}
	//	using meta refresh here because the appliance and resourc class pre-sending header output
	echo "<meta http-equiv=\"refresh\" content=\"0; URL=$url\">";
}


if(htmlobject_request('action') != '') {
$strMsg = '';
$openqrm_server = new openqrm_server();

	switch (htmlobject_request('action')) {
		case 'save':
			$error = 0;

			if(htmlobject_request('appliance_name') != '') {
				if (ereg("^[A-Za-z0-9_-]*$", htmlobject_request('appliance_name')) === false) {
					$strMsg .= 'appliance name must be [A-Za-z0-9_-]<br/>';
					$error = 1;
				} 
			} else {
				$strMsg .= "appliance name can not be empty<br/>";
				$error = 1;
			}
			if (htmlobject_request('appliance_cpuspeed') != '' && ereg("^[0-9]*$", htmlobject_request('appliance_cpuspeed')) === false) {
				$strMsg .= 'CPU-Speed must be [0-9]<br/>';
				$error = 1;
			}			
			if (htmlobject_request('appliance_memtotal') != '' && ereg("^[0-9]*$", htmlobject_request('appliance_memtotal')) === false) {
				$strMsg .= 'Memory must be [0-9]<br/>';
				$error = 1;
			}
			if (htmlobject_request('appliance_swaptotal') != '' && ereg("^[0-9]*$", htmlobject_request('appliance_swaptotal')) === false) {
				$strMsg .= 'Swap must be [0-9]<br/>';
				$error = 1;
			}


			if($error == 0) {
				$appliance_fields = array(
					'appliance_resources' => $_REQUEST['identifier'][0],
					'appliance_name' => htmlobject_request('appliance_name'),
					'appliance_kernelid' => htmlobject_request('appliance_kernelid'),
					'appliance_imageid' => htmlobject_request('appliance_imageid'),
					'appliance_virtualization' => htmlobject_request('appliance_virtualization'),
					'appliance_cpuspeed' => htmlobject_request('appliance_cpuspeed'),
					'appliance_cpumodel' => htmlobject_request('appliance_cpumodel'),
					'appliance_memtotal' => htmlobject_request('appliance_memtotal'),
					'appliance_swaptotal' => htmlobject_request('appliance_swaptotal'),
					'appliance_capabilities' => htmlobject_request('appliance_capabilities'),
					'appliance_comment' => htmlobject_request('appliance_comment'),
					'appliance_id' => openqrm_db_get_free_id('appliance_id', $APPLIANCE_INFO_TABLE),
					'appliance_cluster' => (htmlobject_request('appliance_cluster') == '') ? 0 : 1,
					'appliance_ssi' => (htmlobject_request('appliance_ssi') == '') ? 0 : 1,
					'appliance_highavailable' => (htmlobject_request('appliance_highavailable') == '') ? 0 : 1,
					'appliance_virtual' => (htmlobject_request('appliance_virtual') == '') ? 0 : 1,
				);
				$appliance = new appliance();
				echo $appliance->add($appliance_fields);
				$strMsg .= 'added new appliance';
				redirect($strMsg);
			} 
			else { $_REQUEST['strMsg'] = $strMsg; }

			/*
			echo '<pre>';
			print_r($appliance_fields);
			echo '</pre>';
			*/
		break;
	}
}


function appliance_form() {
	global $OPENQRM_USER;
	global $thisfile;

	$image = new image();
	$image_list = array();
	$image_list = $image->get_list();
	// remove the idle image from the list
	#print_r($image_list);
	array_shift($image_list);

	$kernel = new kernel();
	$kernel_list = array();
	$kernel_list = $kernel->get_list();
	// remove the openqrm kernelfrom the list
	#print_r($kernel_list);
	array_shift($kernel_list);

	$virtualization = new virtualization();
	$virtualization_list = array();
	$virtualization_list = $virtualization->get_list();

	if(count($image_list) > 1) {

		//-------------------------------------- Form second step
		if (htmlobject_request('identifier') != '' && (htmlobject_request('action') == 'select' || isset($_REQUEST['step_2']))) {
	
			//------------------------------------------------------------ set vars
			foreach(htmlobject_request('identifier') as $id) {
				$ident = $id; // resourceid
			}
	
			//------------------------------------------------------------ set template
			$t = new Template_PHPLIB();
			$t->debug = false;
			$t->setFile('tplfile', './' . 'appliance-tpl.php');
			$t->setVar(array(
				'thisfile' => $thisfile,
				'step_2' => htmlobject_input('step_2', array("value" => true, "label" => ''), 'hidden'),
				'identifier' => htmlobject_input('identifier[]', array("value" => $ident, "label" => ''), 'hidden'),
				'currentab' => htmlobject_input('currenttab', array("value" => 'tab1', "label" => ''), 'hidden'),
				'lang_requirements' => 'Requirements',
				'appliance_kernelid' => htmlobject_select('appliance_kernelid', $kernel_list, 'Kernel', array(htmlobject_request('appliance_kernelid'))),
				'appliance_imageid' => htmlobject_select('appliance_imageid', $image_list, 'Image', array(htmlobject_request('appliance_imageid'))),
				'appliance_virtualization' => htmlobject_select('appliance_virtualization', $virtualization_list, 'Resource', array(htmlobject_request('appliance_virtualization'))),
				'appliance_name' => htmlobject_input('appliance_name', array("value" => htmlobject_request('appliance_name'), "label" => 'Name'), 'text', 20),
				'appliance_cpuspeed' => htmlobject_input('appliance_cpuspeed', array("value" => htmlobject_request('appliance_cpuspeed'), "label" => 'CPU-Speed'), 'text', 20),
				'appliance_cpumodel' => htmlobject_input('appliance_cpumodel', array("value" => htmlobject_request('appliance_cpumodel'), "label" => 'CPU-Model'), 'text', 20),
				'appliance_memtotal' => htmlobject_input('appliance_memtotal', array("value" => htmlobject_request('appliance_memtotal'), "label" => 'Memory'), 'text', 20),
				'appliance_swaptotal' => htmlobject_input('appliance_swaptotal', array("value" => htmlobject_request('appliance_swaptotal'), "label" => 'Swap'), 'text', 20),
				'appliance_capabilities' => htmlobject_input('appliance_capabilities', array("value" => htmlobject_request('appliance_capabilities'), "label" => 'Capabilities'), 'text', 255),
				'appliance_comment' => htmlobject_textarea('appliance_comment', array("value" => htmlobject_request('appliance_comment'), "label" => 'Comment')),
				'appliance_cluster' => htmlobject_input('appliance_cluster', array("value" => 1, "label" => 'Cluster'), 'checkbox', (htmlobject_request('appliance_cluster') == '') ? false : true),
				'appliance_ssi' => htmlobject_input('appliance_ssi', array("value" => 1, "label" => 'SSI'), 'checkbox', (htmlobject_request('appliance_ssi') == '') ? false : true),
				'appliance_highavailable' => htmlobject_input('appliance_highavailable', array("value" => 1, "label" => 'Highavailable'), 'checkbox', (htmlobject_request('appliance_highavailable') == '') ? false : true),
				'appliance_virtual' => htmlobject_input('appliance_virtual', array("value" => 1, "label" => 'Virtual'), 'checkbox', (htmlobject_request('appliance_virtual') == '') ? false : true),
				'submit_save' => htmlobject_input('action', array("value" => 'save', "label" => 'save'), 'submit'),
			));
	
			$disp =  $t->parse('out', 'tplfile');
		}	
		//-------------------------------------- Form first step
		else  {
		
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
			$arHead['resource_id']['title'] ='ID';
			$arHead['resource_name'] = array();
			$arHead['resource_name']['title'] ='Name';
			$arHead['resource_ip'] = array();
			$arHead['resource_ip']['title'] ='Ip';
		
			$resource_count=0;
		
			$auto_resource_icon="/openqrm/base/img/resource.png";
			$auto_state_icon="/openqrm/base/img/active.png";
	
			$arBody = array();
			$arBody[] = array(
				'resource_state' => "<img src=$auto_state_icon>",
				'resource_icon' => "<img width=24 height=24 src=$auto_resource_icon>",
				'resource_id' => '-1',
				'resource_name' => "auto-select resource",
				'resource_ip' => "0.0.0.0",
			);
		
			$resource_tmp = new resource();
			$resource_array = $resource_tmp->display_overview(1, 100, 'resource_id', 'ASC');
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
				if (!file_exists($_SERVER["DOCUMENT_ROOT"].$state_icon)) {
					$state_icon="/openqrm/base/img/unknown.png";
				}
				$arBody[] = array(
					'resource_state' => "<img src=$state_icon>",
					'resource_icon' => "<img width=24 height=24 src=$resource_icon_default>",
					'resource_id' => $resource->id,
					'resource_name' => $resource->hostname,
					'resource_ip' => $resource->ip,
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
			$table->max = count($resource_tmp);
			$disp = "<h3>Resource List</h3>". $table->get_string();
		}

	} else {
		$disp = '<center>';
		$disp .= '<b>No Image available</b>';
		$disp .= '<br><br>';
		$disp .= '<a href="../image/image-new.php?currenttab=tab1">Image</a>';
		$disp .= '</center>';
		$disp .= '<br><br>';
	}
		
	return "<h1>New Appliance</h1>". $disp;
}

$output = array();
$output[] = array('label' => 'Appliance List', 'target' => 'appliance-index.php');
$output[] = array('label' => 'New Appliance', 'value' => appliance_form());


?>
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
<link rel="stylesheet" type="text/css" href="appliance.css" />
<?php
echo htmlobject_tabmenu($output);
?>