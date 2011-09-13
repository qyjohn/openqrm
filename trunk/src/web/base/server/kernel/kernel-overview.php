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
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/include/htmlobject.inc.php";
require_once "$RootDir/class/openqrm_server.class.php";
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;

function redirect($strMsg, $currenttab = 'tab0', $url = '') {
	global $thisfile;
	if($url == '') {
		$url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab='.$currenttab;
	}
	header("Location: $url");
	exit;
}


if(htmlobject_request('action') != '' && $OPENQRM_USER->role == "administrator") {
	$strMsg = '';

	switch (htmlobject_request('action')) {
		case '删除':
			$kernel = new kernel();
			if(isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $id) {
					// check that this is not the default kernel
					if ($id == 1) {
						$strMsg .= "Not removing the default kernel!<br>";
						continue;
					}
					// check that this kernel is not in use any more
					$kernel_is_used_by_appliance = "";
					$remove_error = 0;
					$appliance = new appliance();
					$appliance_id_list = $appliance->get_all_ids();
					foreach($appliance_id_list as $appliance_list) {
						$appliance_id = $appliance_list['appliance_id'];
						$app_kernel_remove_check = new appliance();
						$app_kernel_remove_check->get_instance_by_id($appliance_id);
						if ($app_kernel_remove_check->kernelid == $id) {
							$kernel_is_used_by_appliance .= $appliance_id." ";
							$remove_error = 1;
						}
					}
					if ($remove_error == 1) {
						$strMsg .= "Kernel id ".$id." is used by appliance(s): ".$kernel_is_used_by_appliance." <br>";
						$strMsg .= "Not removing kernel id ".$id." !<br>";
						continue;
					}

					$strMsg .= $kernel->remove($id);
				}
			}
			redirect($strMsg);
			break;

		case '默认':
			$kernel = new kernel();
			if(isset($_REQUEST['identifier'])) {
				foreach($_REQUEST['identifier'] as $id) {
					// update default kernel in db
					$kernel->get_instance_by_id($id);
					$ar_kernel_update = array(
						'kernel_name' => "default",
						'kernel_version' => $kernel->version,
						'kernel_capabilities' => $kernel->capabilities,
					);
					$kernel->update(1, $ar_kernel_update);
					// send set-default kernel command to openQRM
					$openqrm_server->send_command("openqrm_server_set_default_kernel $kernel->name");
					$strMsg .= "Set kernel ".$kernel->name." as the default kernel";
					break;
				}
				redirect($strMsg);
			}
			break;

		case '升级':
			$kernel_id = htmlobject_request("kernel_id");
			$kernel_comment = htmlobject_request("kernel_comment");

			// check that this kernel is not in use any more
			$kernel_is_used_by_appliance = "";
			$update_error = 0;
			$appliance = new appliance();
			$appliance_id_list = $appliance->get_all_ids();
			foreach($appliance_id_list as $appliance_list) {
				$appliance_id = $appliance_list['appliance_id'];
				$app_kernel_update_check = new appliance();
				$app_kernel_update_check->get_instance_by_id($appliance_id);
				if (!strcmp($app_kernel_update_check->state, "stopped")) {
					continue;
				}
				if ($app_kernel_update_check->kernelid == $kernel_id) {
					$kernel_is_used_by_appliance .= $appliance_id." ";
					$update_error = 1;
				}
			}
			if ($update_error == 1) {
				$strMsg .= "Kernel id ".$kernel_id." is used by appliance(s): ".$kernel_is_used_by_appliance." <br>";
				$strMsg .= "Not updating kernel id ".$kernel_id." !<br>";
			} else {
				$kernel = new kernel();
				$kernel_fields = array();
				$kernel_fields['kernel_comment'] = $kernel_comment;
				$kernel->update($kernel_id, $kernel_fields);
				$strMsg .= "Updated kernel id ".$kernel_id." <br>";
			}
			redirect($strMsg);
			break;

	}

}




function kernel_display() {
	global $OPENQRM_USER;
	global $thisfile;

	$kernel_tmp = new kernel();
	$table = new htmlobject_db_table('kernel_id');

	$disp = '<h1>内核列表</h1>';
	$disp .= '<br>';

	$arHead = array();
	$arHead['kernel_icon'] = array();
	$arHead['kernel_icon']['title'] ='';

	$arHead['kernel_id'] = array();
	$arHead['kernel_id']['title'] ='编号';

	$arHead['kernel_name'] = array();
	$arHead['kernel_name']['title'] ='名称';

	$arHead['kernel_version'] = array();
	$arHead['kernel_version']['title'] ='版本';

	$arHead['kernel_comment'] = array();
	$arHead['kernel_comment']['title'] ='说明';

	$arBody = array();
	$kernel_array = $kernel_tmp->display_overview($table->offset, $table->limit, $table->sort, $table->order);

	$kernel_icon = "/openqrm/base/img/kernel.png";
	foreach ($kernel_array as $index => $kernel_db) {
		$kernel = new kernel();
		$kernel->get_instance_by_id($kernel_db["kernel_id"]);
		$arBody[] = array(
			'kernel_icon' => "<img width=20 height=20 src=$kernel_icon>",
			'kernel_id' => $kernel_db["kernel_id"],
			'kernel_name' => $kernel_db["kernel_name"],
			'kernel_version' => $kernel_db["kernel_version"],
			'kernel_comment' => $kernel_db["kernel_comment"],
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
	$table->identifier_disabled = array(1);
	if ($OPENQRM_USER->role == "administrator") {
		$table->bottom = array('编辑', '默认', '删除');
		$table->identifier = 'kernel_id';
	}
		$kernel_max = $kernel_tmp->get_count();
	$table->max = $kernel_max - 1;
	#$table->limit = 10;

	return $disp.$table->get_string();
}


function kernel_form() {

	$disp = "<h1>创建内核</h1>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<b>向openqrm服务器添加新内核的命令下：</b><br>";
	$disp = $disp."<br>";
	$disp = $disp."<br>/usr/share/openqrm/bin/openqrm kernel add -n name -v version -u username -p password [-l location] [-i initramfs/ext2] [-t path-to-initrd-template-file]<br>";
	$disp = $disp."<br>";
	$disp = $disp."<b>name</b> 内核的名称，是一个不含空格或者其他特殊字符的字符串，该字符串被用作内核文件名的一部分。<br>";
	$disp = $disp."<b>version</b> 内核的版本号。例如，假设内核的文件名为 vmlinuz-2.6.26-2-amd64 ，则 2.6.26-2-amd64 就是该内核的版本号。<br>";
	$disp = $disp."<b>username</b> 和 <b>password</b> 是您登陆进入openqrm的用户名和密码。<br>";
	$disp = $disp."<b>location</b> 是安装该内核的根目录。内核文件的全路径为 \${location}/boot/vmlinuz-\${version}, \${location}/boot/initrd.img-\${version} and \${location}/lib/modules/\${version}/* 。<br>";
	$disp = $disp."<b>initramfs/ext2</b> initrd映像文件的文件系统类型。最常用的文件系统为 <b>initramfs</b> 。<br>";
	$disp = $disp."<b>path-to-initrd-template-file</b> 一个initrd模版的全路径。这些模版可以在openqrm的base目录下的 etc/templates 目录内。<br>";
	$disp = $disp."<br>";
	$disp = $disp."例如：<br>";
	$disp = $disp."/usr/share/openqrm/bin/openqrm kernel add -n openqrm-kernel-1 -v 2.6.29 -u openqrm -p openqrm -i initramfs -l / -t /usr/share/openqrm/etc/templates/openqrm-initrd-template.debian.x86_64.tgz<br>";
	$disp = $disp."<br>";
	return $disp;
}


function kernel_edit($kernel_id) {
	global $thisfile;
	if (!strlen($kernel_id))  {
		echo "没有选中任何内核！";
		exit(0);
	}

	$kernel = new kernel();
	$kernel->get_instance_by_id($kernel_id);

	$disp = "<h1>编辑内核</h1>";
	$disp = $disp."<form action='".$thisfile."' method=post>";
	$disp = $disp."<br>";
	$disp = $disp."<br>";
	$disp = $disp."<b>".$kernel->name."</b><br>";
	$disp = $disp.htmlobject_input('kernel_comment', array("value" => $kernel->comment, "label" => '说明'), 'text', 100);
	//	$disp = $disp.htmlobject_input('kernel_version', array("value" => $kernel->version, "label" => ' Kernel version'), 'text', 20);
	$disp = $disp."<input type=hidden name=kernel_id value=$kernel_id>";
	$disp = $disp."<input type=hidden name=action value='update'>";
	$disp = $disp."<input type=submit value='更新'>";
	$disp = $disp."";
	$disp = $disp."";
	$disp = $disp."";
	$disp = $disp."";
	$disp = $disp."";
	$disp = $disp."</form>";
	return $disp;
}





$output = array();
if($OPENQRM_USER->role == "administrator") {
	if(htmlobject_request('action') != '') {
		if(isset($_REQUEST['identifier'])) {
			switch (htmlobject_request('action')) {
				case '编辑':
					foreach($_REQUEST['identifier'] as $id) {
						$output[] = array('label' => '编辑内核', 'value' => kernel_edit($id));
						break;
					}
					break;
			}
		} else {
			$output[] = array('label' => '管理内核', 'value' => kernel_display());
			$output[] = array('label' => '创建内核', 'value' => kernel_form());
		}
	} else {
		$output[] = array('label' => '管理内核', 'value' => kernel_display());
		$output[] = array('label' => '创建内核', 'value' => kernel_form());
	}
}


?>
<link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
<link rel="stylesheet" type="text/css" href="kernel.css" />

<?php
echo htmlobject_tabmenu($output);
?>
