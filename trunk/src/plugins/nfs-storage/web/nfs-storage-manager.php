<!doctype html>
<html lang="en">
<head>
	<title>LVM Storage manager</title>
    <link rel="stylesheet" type="text/css" href="../../css/htmlobject.css" />
    <link rel="stylesheet" type="text/css" href="nfs-storage.css" />
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
$nfs_storage_id = htmlobject_request('nfs_storage_id');
$nfs_storage_name = htmlobject_request('nfs_storage_id');
$nfs_lun_name = htmlobject_request('nfs_lun_name');
$nfs_lun_snap_name = htmlobject_request('nfs_lun_snap_name');
$nfs_storage_fields = array();
foreach ($_REQUEST as $key => $value) {
	if (strncmp($key, "nfs_storage_", 11) == 0) {
		$nfs_storage_fields[$key] = $value;
	}
}
global $nfs_storage_id;
global $nfs_storage_name;
global $nfs_lun_name;
global $nfs_lun_snap_name;

$refresh_delay=1;
$refresh_loop_max=20;


function redirect_nfs($strMsg, $nfs_storage_id) {
    global $thisfile;
    global $action;
    $url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab=tab0&redirect=yes&action='.$action.'&identifier[]='.$nfs_storage_id;
    echo "<meta http-equiv=\"refresh\" content=\"0; URL=$url\">";
    exit;
}

function redirect_nfs_mgmt($strMsg, $nfs_storage_id) {
    global $thisfile;
    global $action;
    $url = $thisfile.'?strMsg='.urlencode($strMsg).'&currenttab=tab0&redirect=yes&action='.$action.'&nfs_storage_id='.$nfs_storage_id;
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


// running the actions
if(htmlobject_request('redirect') != 'yes') {
    if(htmlobject_request('action') != '') {
        switch (htmlobject_request('action')) {
            case 'select':
                if (isset($_REQUEST['identifier'])) {
                    foreach($_REQUEST['identifier'] as $id) {
                        show_progressbar();
                        $storage = new storage();
                        $storage->get_instance_by_id($id);
                        $storage_resource = new resource();
                        $storage_resource->get_instance_by_id($storage->resource_id);
                        $resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nfs-storage/bin/openqrm-nfs-storage post_exports -u $OPENQRM_USER->name -p $OPENQRM_USER->password";
                        // remove current stat file
                        $storage_resource_id = $storage_resource->id;
                        $statfile="storage/".$storage_resource_id.".nfs.stat";
                        if (file_exists($statfile)) {
                            unlink($statfile);
                        }
                        // send command
                        $storage_resource->send_command($storage_resource->ip, $resource_command);
                        // and wait for the resulting statfile
                        if (!wait_for_statfile($statfile)) {
                            $redir_msg = "Error during refreshing NFS volumes ! Please check the Event-Log";
                        } else {
                            $redir_msg = "Displaying NFS volumes on storage id $id";
                        }
                        redirect_nfs($redir_msg, $id);
                    }
                }
                break;


            case 'reload':
                if (strlen($nfs_storage_id)) {
                    show_progressbar();
                    $storage = new storage();
                    $storage->get_instance_by_id($nfs_storage_id);
                    $storage_resource = new resource();
                    $storage_resource->get_instance_by_id($storage->resource_id);
                    $resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nfs-storage/bin/openqrm-nfs-storage post_exports -u $OPENQRM_USER->name -p $OPENQRM_USER->password";
                    // remove current stat file
                    $storage_resource_id = $storage_resource->id;
                    $statfile="storage/".$storage_resource_id.".nfs.stat";
                    if (file_exists($statfile)) {
                        unlink($statfile);
                    }
                    // send command
                    $storage_resource->send_command($storage_resource->ip, $resource_command);
                    // and wait for the resulting statfile
                    if (!wait_for_statfile($statfile)) {
                        $redir_msg = "Error during refreshing NFS volumes ! Please check the Event-Log";
                    } else {
                        $redir_msg = "Displaying NFS volumes on storage id $nfs_storage_id";
                    }
                    redirect_nfs($redir_msg, $nfs_storage_id);
                }
                break;

            case 'add':
                if (strlen($nfs_storage_id)) {
                    show_progressbar();
                    if (!strlen($nfs_lun_name)) {
                        $redir_msg = "Got emtpy NFS volume name. Not adding ...";
                        redirect_nfs_mgmt($redir_msg, $nfs_storage_id);
                        exit(0);
                    }
                    $storage = new storage();
                    $storage->get_instance_by_id($nfs_storage_id);
                    $storage_resource = new resource();
                    $storage_resource->get_instance_by_id($storage->resource_id);
                    $resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nfs-storage/bin/openqrm-nfs-storage add -n $nfs_lun_name -u $OPENQRM_USER->name -p $OPENQRM_USER->password";
                    // remove current stat file
                    $storage_resource_id = $storage_resource->id;
                    $statfile="storage/".$storage_resource_id.".nfs.stat";
                    if (file_exists($statfile)) {
                        unlink($statfile);
                    }
                    // send command
                    $storage_resource->send_command($storage_resource->ip, $resource_command);
                    // and wait for the resulting statfile
                    if (!wait_for_statfile($statfile)) {
                        $redir_msg .= "Error during adding NFS volume $nfs_lun_name ! Please check the Event-Log<br>";
                    } else {
                        $redir_msg .= "Added NFS volume $nfs_lun_name to storage id $nfs_storage_id<br>";
                    }
                    redirect_nfs_mgmt($redir_msg, $nfs_storage_id);
                }
                break;


            case 'remove':
                if (strlen($nfs_storage_id)) {
                    show_progressbar();
                    if (isset($_REQUEST['identifier'])) {
                        $storage = new storage();
                        $storage->get_instance_by_id($nfs_storage_id);
                        $storage_resource = new resource();
                        $storage_resource->get_instance_by_id($storage->resource_id);
                        // remove current stat file
                        $storage_resource_id = $storage_resource->id;
                        $statfile="storage/".$storage_resource_id.".nfs.stat";
                        if (file_exists($statfile)) {
                            unlink($statfile);
                        }
                        // send command
                        foreach($_REQUEST['identifier'] as $nfs_lun_name) {
                            $resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nfs-storage/bin/openqrm-nfs-storage remove -n $nfs_lun_name -u $OPENQRM_USER->name -p $OPENQRM_USER->password";
                            $storage_resource->send_command($storage_resource->ip, $resource_command);
                            $redir_msg .= "Removed NFS volume $nfs_lun_name from storage id $nfs_storage_id<br>";
                            sleep(2);
                        }
                        // and wait for the resulting statfile
                        if (!wait_for_statfile($statfile)) {
                            $redir_msg = "Error during removing NFS volume ! Please check the Event-Log<br>";
                        }
                        redirect_nfs_mgmt($redir_msg, $nfs_storage_id);
                    } else {
                        $redir_msg = "No NFS volume selected. Skipping removal !";
                        redirect_nfs_mgmt($redir_msg, $nfs_storage_id);
                    }
                }
                break;

            case 'snap':
                if (strlen($nfs_storage_id)) {
                    show_progressbar();
                    if (!strlen($nfs_lun_name)) {
                        $redir_msg = "Got emtpy NFS volume name. Not adding ...";
                        redirect_nfs_mgmt($redir_msg, $nfs_storage_id);
                        exit(0);
                    }
                    if (!strlen($nfs_lun_snap_name)) {
                        $redir_msg = "Got emtpy NFS volume snapshot name. Not adding ...";
                        redirect_nfs_mgmt($redir_msg, $nfs_storage_id);
                        exit(0);
                    }
                    $storage = new storage();
                    $storage->get_instance_by_id($nfs_storage_id);
                    $storage_resource = new resource();
                    $storage_resource->get_instance_by_id($storage->resource_id);
                    // remove current stat file
                    $storage_resource_id = $storage_resource->id;
                    $statfile="storage/".$storage_resource_id.".nfs.stat";
                    if (file_exists($statfile)) {
                        unlink($statfile);
                    }
                    // send command
                    $resource_command="$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/nfs-storage/bin/openqrm-nfs-storage snap -n $nfs_lun_name -s $nfs_lun_snap_name -u $OPENQRM_USER->name -p $OPENQRM_USER->password";
                    $storage_resource->send_command($storage_resource->ip, $resource_command);
                    // and wait for the resulting statfile
                    if (!wait_for_statfile($statfile)) {
                        $redir_msg .= "Error during snapshotting NFS volume $nfs_lun_name ! Please check the Event-Log<br>";
                    } else {
                        $redir_msg .= "Cloned NFS volume $nfs_lun_name on storage id $nfs_storage_id<br>";
                    }
                    redirect_nfs_mgmt($redir_msg, $nfs_storage_id);
                }
                break;
        }
    }
}



function nfs_select_storage() {
	global $OPENQRM_USER;
	global $thisfile;
	$table = new htmlobject_db_table('storage_id');

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

	$storage_count=0;
	$arBody = array();
	$storage_tmp = new storage();
	$storage_array = $storage_tmp->display_overview(0, 10, 'storage_id', 'ASC');
	foreach ($storage_array as $index => $storage_db) {
		$storage = new storage();
		$storage->get_instance_by_id($storage_db["storage_id"]);
		$storage_resource = new resource();
		$storage_resource->get_instance_by_id($storage->resource_id);
		$deployment = new deployment();
		$deployment->get_instance_by_id($storage->type);
		// is nfs-storage ?
		if ("$deployment->storagetype" == "nfs-storage") {
			$storage_count++;
			$resource_icon_default="/openqrm/base/img/resource.png";
			$storage_icon="/openqrm/base/plugins/nfs-storage/img/storage.png";
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
				'storage_comment' => $storage_resource->comment,
			);
		}
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
	$table->max = $storage_count;

   // set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './tpl/' . 'nfs-storage-select.tpl.php');
	$t->setVar(array(
		'formaction' => $thisfile,
		'storage_server_table' => $table->get_string(),
	));
	$disp =  $t->parse('out', 'tplfile');
	return $disp;

}



function nfs_storage_display($nfs_storage_id) {
	global $OPENQRM_USER;
	global $thisfile;
	$storage = new storage();
	$storage->get_instance_by_id($nfs_storage_id);
	$storage_resource = new resource();
	$storage_resource->get_instance_by_id($storage->resource_id);
	$deployment = new deployment();
	$deployment->get_instance_by_id($storage->type);

	$table = new htmlobject_db_table('nfs_luns');
	$arHead = array();

    $arHead['nfs_lun_icon'] = array();
	$arHead['nfs_lun_icon']['title'] ='';

	$arHead['nfs_lun_name'] = array();
	$arHead['nfs_lun_name']['title'] ='Name';

	$arHead['nfs_lun'] = array();
	$arHead['nfs_lun']['title'] ='exported to';

	$arHead['nfs_lun_snap'] = array();
	$arHead['nfs_lun_snap']['title'] ='Clone (name)';

	$arBody = array();
	$nfs_count=1;
	$storage_icon="/openqrm/base/plugins/nfs-storage/img/storage.png";

	$storage_export_list="storage/$storage_resource->id.nfs.stat";
	if (file_exists($storage_export_list)) {
		$storage_vg_content=file($storage_export_list);
		foreach ($storage_vg_content as $index => $nfs) {
			// find export name
            $nfs_line = trim($nfs);
            $first_at_pos = strpos($nfs_line, "@");
            $first_at_pos++;
            $nfs_line_first_at_removed = substr($nfs_line, $first_at_pos, strlen($nfs_line)-$first_at_pos);
            $second_at_pos = strpos($nfs_line_first_at_removed, "@");
            $second_at_pos++;
            $nfs_line_second_at_removed = substr($nfs_line_first_at_removed, $second_at_pos, strlen($nfs_line_first_at_removed)-$second_at_pos);
            $third_at_pos = strpos($nfs_line_second_at_removed, "@");
            $third_at_pos++;
            $nfs_line_third_at_removed = substr($nfs_line_second_at_removed, $third_at_pos, strlen($nfs_line_second_at_removed)-$third_at_pos);
            $fourth_at_pos = strpos($nfs_line_third_at_removed, "@");
            $fourth_at_pos++;
            $nfs_line_fourth_at_removed = substr($nfs_line_third_at_removed, $fourth_at_pos, strlen($nfs_line_third_at_removed)-$fourth_at_pos);
            $fivth_at_pos = strpos($nfs_line_fourth_at_removed, "@");
            $fivth_at_pos++;
            $nfs_line_fivth_at_removed = substr($nfs_line_fourth_at_removed, $fivth_at_pos, strlen($nfs_line_fourth_at_removed)-$fivth_at_pos);
            $sixth_at_pos = strpos($nfs_line_fivth_at_removed, "@");
            $sixth_at_pos++;
            $nfs_line_sixth_at_removed = substr($nfs_line_fivth_at_removed, $sixth_at_pos, strlen($nfs_line_fivth_at_removed)-$sixth_at_pos);
            $seventh_at_pos = strpos($nfs_line_sixth_at_removed, "@");
            $seventh_at_pos++;

            $nfs_lun_name = basename(trim(substr($nfs_line, 0, $first_at_pos-1)));
            $nfs_lun_export = trim(substr($nfs_line_first_at_removed, 0));
            // build the snap-shot input
            $nfs_lun_snap = "<form action=\"$thisfile\" method=\"GET\">";
            $nfs_lun_snap .= "<input type='hidden' name='nfs_storage_id' value=$nfs_storage_id>";
            $nfs_lun_snap .= "<input type='hidden' name='nfs_lun_name' value=$nfs_lun_name>";
            $nfs_lun_snap .= "<input type='text' name='nfs_lun_snap_name' value='' size='10' maxlength='20'>";
            $nfs_lun_snap .= "<input type='submit' name='action' value='snap'>";
            $nfs_lun_snap .= "</form>";

            $arBody[] = array(
                'nfs_lun_icon' => "<img width=24 height=24 src=$storage_icon><input type='hidden' name='nfs_storage_id' value=$nfs_storage_id>",
                'nfs_lun_name' => $nfs_lun_name,
                'nfs_lun' => $nfs_lun_export,
                'nfs_lun_snap' => $nfs_lun_snap,
            );
            $nfs_lun_count++;
		}
	}

	$table->id = 'Tabelle';
	$table->css = 'htmlobject_table';
	$table->border = 1;
	$table->cellspacing = 0;
	$table->cellpadding = 3;
	$table->form_action = $thisfile;
    $table->identifier_type = "radio";
	$table->sort = '';
	$table->head = $arHead;
	$table->body = $arBody;
	if ($OPENQRM_USER->role == "administrator") {
		$table->bottom = array('reload', 'remove');
		$table->identifier = 'nfs_lun_name';
	}
	$table->max = $nfs_count;

    // set template
	$t = new Template_PHPLIB();
	$t->debug = false;
	$t->setFile('tplfile', './tpl/' . 'nfs-storage-luns.tpl.php');
	$t->setVar(array(
		'formaction' => $thisfile,
		'storage_name' => $storage->name,
		'lun_table' => $table->get_string(),
		'nfs_lun_name' => htmlobject_input('nfs_lun_name', array("value" => '', "label" => 'Lun Name'), 'text', 20),
    	'hidden_nfs_storage_id' => "<input type='hidden' name='nfs_storage_id' value=$nfs_storage_id>",
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
                    $output[] = array('label' => 'Nfs Storage Admin', 'value' => nfs_storage_display($id));
                }
            } else {
            	$output[] = array('label' => 'Select', 'value' => nfs_select_storage());
            }
			break;
        
		case 'reload':
            if (isset($_REQUEST['identifier'])) {
                foreach($_REQUEST['identifier'] as $id) {
                    $output[] = array('label' => 'Nfs Storage Admin', 'value' => nfs_storage_display($id));
                }
            }
			break;

        case 'add':
            if (strlen($nfs_storage_id)) {
                $output[] = array('label' => 'Nfs Storage Admin', 'value' => nfs_storage_display($nfs_storage_id));
            } else {
            	$output[] = array('label' => 'Select', 'value' => nfs_select_storage());
            }
			break;

        case 'remove':
            if (strlen($nfs_storage_id)) {
                $output[] = array('label' => 'Nfs Storage Admin', 'value' => nfs_storage_display($nfs_storage_id));
            } else {
            	$output[] = array('label' => 'Select', 'value' => nfs_select_storage());
            }
			break;

        case 'snap':
            if (strlen($nfs_storage_id)) {
                $output[] = array('label' => 'Nfs Storage Admin', 'value' => nfs_storage_display($nfs_storage_id));
            } else {
            	$output[] = array('label' => 'Select', 'value' => nfs_select_storage());
            }
			break;


	}
} else if (strlen($nfs_storage_id)) {
	$output[] = array('label' => 'Nfs Storage Admin', 'value' => nfs_storage_display($nfs_storage_id));
} else  {
	$output[] = array('label' => 'Select', 'value' => nfs_select_storage());
}


echo htmlobject_tabmenu($output);

?>


