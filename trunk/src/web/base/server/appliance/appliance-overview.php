
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />

<?php

$RootDir = $_SERVER["DOCUMENT_ROOT"].'openqrm/base/';
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/include/htmlobject.inc.php";

function appliance_htmlobject_select($name, $value, $title = '', $selected = '') {
		$html = new htmlobject_select();
		$html->name = $name;
		$html->title = $title;
		$html->selected = $selected;
		$html->text_index = array("value" => "value", "text" => "label");
		$html->text = $value;
		return $html->get_string();
}

function appliance_display($admin) {
	$appliance_tmp = new appliance();
	$OPENQRM_APPLIANCES_COUNT = $appliance_tmp->get_count();

	if ("$admin" == "admin") {
		$disp = "<b>Appliance Admin</b>";
	} else {
		$disp = "<b>Appliance overview</b>";
	}
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."All appliances: $OPENQRM_APPLIANCES_COUNT";
	$disp = $disp."<br>";
	$appliance_array = $appliance_tmp->display_overview(0, 10);
	foreach ($appliance_array as $index => $appliance_db) {
		$appliance = new appliance();
		$appliance->get_instance_by_id($appliance_db["appliance_id"]);

		$disp = $disp."<div id=\"appliance\" nowrap=\"true\">";
		$disp = $disp."<form action='appliance-action.php' method=post>";
		$disp = $disp."$appliance->id $appliance->name ";
		$disp = $disp."<input type=hidden name=appliance_id value=$appliance->id>";
		$disp = $disp."<input type=hidden name=appliance_name value=$appliance->name>";
		$disp = $disp."<input type=hidden name=appliance_command value='remove'";
		if ("$admin" == "admin") {
			$disp = $disp."<input type=submit value='remove'>";
		}
		$disp = $disp."</form>";
		$disp = $disp."</div>";
	}
	return $disp;
}



function appliance_form() {

	$image = new image();
	$image_list = array();
	$image_list = $image->get_list();
	// remove the idle image from the list
	array_splice($image_list, 0, 1);

	$kernel = new kernel();
	$kernel_list = array();
	$kernel_list = $kernel->get_list();

	$disp = "<b>New Appliance</b>";
	$disp = $disp."<form action='appliance-action.php' method=post>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp.htmlobject_input('appliance_name', array("value" => '', "label" => 'Appliance name'), 'text', 20);
	$disp = $disp."<br>";
	$disp = $disp."Kernel ";
	$kernel_select = appliance_htmlobject_select('resource_kernelid', $kernel_list, '', $kernel_list);
	$disp = $disp.$kernel_select;
	$disp = $disp."<br>";
	$disp = $disp."Server-Image ";
	$image_select = appliance_htmlobject_select('resource_imageid', $image_list, 'Select image', $image_list);
	$disp = $disp.$image_select;
	$disp = $disp."<br>";
	$disp = $disp."Requirements";
	$disp = $disp."<br>";
	$disp = $disp.htmlobject_input('appliance_cpuspeed', array("value" => '', "label" => 'CPU-Speed'), 'text', 20);
	$disp = $disp.htmlobject_input('appliance_cpumodel', array("value" => '', "label" => 'CPU-Model'), 'text', 20);
	$disp = $disp.htmlobject_input('appliance_memtotal', array("value" => '', "label" => 'Memory'), 'text', 20);
	$disp = $disp.htmlobject_input('appliance_swaptotal', array("value" => '', "label" => 'Swap'), 'text', 20);
	$disp = $disp.htmlobject_input('appliance_capabilities', array("value" => '', "label" => 'Capabilities'), 'text', 255);
    $disp = $disp."<input type='checkbox' name='appliance_cluster' value='0'> Cluster<br>";
    $disp = $disp."<input type='checkbox' name='appliance_ssi' value='0'> SSI<br>";
    $disp = $disp."<input type='checkbox' name='appliance_highavailable' value='0'> High-Available<br>";
    $disp = $disp."<input type='checkbox' name='appliance_virtual' value='0'> Virtual<br>";
    $disp = $disp."<input type='checkbox' name='appliance_cluster' value='0'> Cluster<br>";



	$disp = $disp."<input type=hidden name=appliance_command value='new_appliance'>";
	$disp = $disp."<input type=submit value='add'>";
	$disp = $disp."";
	$disp = $disp."";
	$disp = $disp."";
	$disp = $disp."";
	$disp = $disp."";
	$disp = $disp."</form>";
	return $disp;
}


// user/role authentication
$user = new user($_SERVER['PHP_AUTH_USER']);
$user->set_user();

$output = array();
// all user
$output[] = array('label' => 'Appliance-List', 'value' => appliance_display(""));
// if admin
if ($user->role == "administrator") {
	$output[] = array('label' => 'New', 'value' => appliance_form());
	$output[] = array('label' => 'Appliance-Admin', 'value' => appliance_display("admin"));
}

echo htmlobject_tabmenu($output);

?>


