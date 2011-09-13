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

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/user.inc.php";
require_once "$RootDir/class/event.class.php";
require_once "$RootDir/class/image.class.php";
require_once "$RootDir/class/image_authentication.class.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/class/kernel.class.php";
require_once "$RootDir/class/virtualization.class.php";

$event = new event();
global $event;

global $OPENQRM_SERVER_BASE_DIR;
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $RESOURCE_INFO_TABLE;

global $vmware_mac_address_space;
$vmware_mac_address_space = "00:50:56:20";

// timout for starting a host from power-off
global $host_start_from_off_timeout;
$host_start_from_off_timeout=360;


// finds a phys resource in powered off state fitting to the appliance
// this is for fail-over of physical systems
function find_phys_resource_poweroff($app) {
	global $event;
	$found_new_resource=0;
	$event->log("find_phys_resource_poweroff", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Trying to find a offline physical resource for appliance $app->name .", "", "", 0, 0, $resource_id);
	$appliance_virtualization=1;

	$resource_tmp = new resource();
	$resource_list = array();
	$resource_list = $resource_tmp->get_list();
	$resource = new resource();
	foreach ($resource_list as $index => $resource_db) {
		$resource->get_instance_by_id($resource_db["resource_id"]);
		if (($resource->id > 0) && ("$resource->imageid" == "1") && ("$resource->state" == "off")) {
			$new_resource_id = $resource->id;
			// check resource-type
			$restype_id = $resource->vtype;
			if ($restype_id == $appliance_virtualization) {
				// check the rest of the required parameters for the appliance
				// cpu-number
				if ((strlen($app->cpunumber)) && (strcmp($app->cpunumber, "0"))) {
					if (strcmp($app->cpunumber, $resource->cpunumber)) {
						$event->log("find_phys_resource_poweroff", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Found new resource $resource->id for appliance $app->name but it has the wrong CPU-number, skipping.", "", "", 0, 0, $resource_id);
						continue;
					}
				}
				// cpu-speed
				if ((strlen($app->cpuspeed)) && (strcmp($app->cpuspeed, "0"))) {
					if (strcmp($app->cpuspeed, $resource->cpuspeed)) {
						$event->log("find_phys_resource_poweroff", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Found new resource $resource->id for appliance $app->name but it has the wrong CPU-speed, skipping.", "", "", 0, 0, $resource_id);
						continue;
					}
				}
				// cpu-model
				if ((strlen($app->cpumodel)) && (strcmp($app->cpumodel, "0"))) {
					if (strcmp($app->cpumodel, $resource->cpumodel)) {
						$event->log("find_phys_resource_poweroff", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Found new resource $resource->id for appliance $app->name but it has the wrong CPU-model, skipping.", "", "", 0, 0, $resource_id);
						continue;
					}
				}
				// memtotal
				if ((strlen($app->memtotal)) && (strcmp($app->memtotal, "0"))) {
					if (strcmp($app->memtotal, $resource->memtotal)) {
						$event->log("find_phys_resource_poweroff", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Found new resource $resource->id for appliance $app->name but it has the wrong amount of Memory, skipping.", "", "", 0, 0, $resource_id);
						continue;
					}
				}
				// swaptotal
				if ((strlen($app->swaptotal)) && (strcmp($app->swaptotal, "0"))) {
					if (strcmp($app->swaptotal, $resource->swaptotal)) {
						$event->log("find_phys_resource_poweroff", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Found new resource $resource->id for appliance $app->name but it has the wrong amount of Swap, skipping.", "", "", 0, 0, $resource_id);
						continue;
					}
				}
				// nics
				if ((strlen($app->nics)) && (strcmp($app->nics, "0"))) {
					if (strcmp($app->nics, $resource->nics)) {
						$event->log("find_phys_resource_poweroff", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Found new resource $resource->id for appliance $app->name but it has the wrong nic count, skipping.", "", "", 0, 0, $resource_id);
						continue;
					}
				}

				// resource can start-from-off (SFO) ?
				$resource_can_start_from_off = $resource->get_resource_capabilities("SFO");
				if ($resource_can_start_from_off == 1) {
					$event->log("find_phys_resource_poweroff", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Found new resource $resource->id for appliance $app->name with start-from-off enabled.", "", "", 0, 0, $resource_id);
				} else {
					$event->log("find_phys_resource_poweroff", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Found new resource $resource->id for appliance $app->name but it cannot start-from-off, skipping.", "", "", 0, 0, $resource_id);
					continue;
				}

				$found_new_resource=1;
				$event->log("find_phys_resource_poweroff", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Found new resource $resource->id for appliance $app->name .", "", "", 0, 0, $resource_id);
				break;
			}
		}
	}
	// in case no resources are available log another ha-error event !
	if ($found_new_resource == 0) {
		$event->log("find_phys_resource_poweroff", $_SERVER['REQUEST_TIME'], 4, "openqrm-highavailability-ha-hook.php", "Could not find a free resource for appliance $app->name !", "", "", 0, 0, 0);
		return $app;
	}

	// if we find an resource which fits to the appliance we update it
	$appliance_fields = array();
	$appliance_fields['appliance_resources'] = $new_resource_id;
	$app->update($app->id, $appliance_fields);

	return $app;
}



// finds a VM host fitting to the virtualization technology of the appliance resource
function find_virtualization_host($v_plugin_name, $avoid_resource_id) {
	global $event;
	$vhost_type = new virtualization();
	$vhost_type->get_instance_by_type($v_plugin_name);
	$event->log("find_virtualization_host", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Trying to find a Virtualizatin Host type  $vhost_type->type $vhost_type->name", "", "", 0, 0, 0);

	// for all in appliance list, find virtualization host appliances
	$appliance_tmp = new appliance();
	$appliance_id_list = $appliance_tmp->get_all_ids();
	$active_appliance_list = array();
	$active_appliance_list_without_origin_vhost = array();
	foreach($appliance_id_list as $id_arr) {
		foreach($id_arr as $id) {
			$appliance = new appliance();
			$appliance->get_instance_by_id($id);
			// active ?
			if ($appliance->stoptime == 0 || $appliance->resources == 0) {
				if ($appliance->virtualization == $vhost_type->id) {
					// we have found an active appliance from the right virtualization type
					$active_appliance_list[] .= $id;
					// if not origin vhost add to second list
					if ($appliance->resources != $avoid_resource_id) {
						$active_appliance_list_without_origin_vhost[] .= $id;
					}

				}
			}
		}
	}
	// really avoid origin host for now
	$active_appliance_list = $active_appliance_list_without_origin_vhost;

	// did we found any ?
	if (count($active_appliance_list) < 1) {
		$event->log("find_virtualization_host", $_SERVER['REQUEST_TIME'], 2, "openqrm-highavailability-ha-hook.php", "Warning ! There is no virtualization host type $vhost_type->name available to bring up a new VM", "", "", 0, 0, 0);
		return -1;
	}
		// if we found more than one host remove the origin vhostid of the failed-resource
	//	if (count($active_appliance_list) > 1) {
	//		$active_appliance_list = $active_appliance_list_without_origin_vhost;
	//	}

	// find the appliance with the most capacities on it
	$max_resourc_load = 100;
	$less_load_resource_id=-1;
	foreach($active_appliance_list as $active_id) {
		$active_appliance = new appliance();
		$active_appliance->get_instance_by_id($active_id);
		$resource = new resource();
		$resource->get_instance_by_id($active_appliance->resources);
		if ($resource->load < $max_resourc_load) {
			$max_resourc_load = $resource->load;
			$less_load_resource_id = $resource->id;
		}
	}
	if ($less_load_resource_id >= 0) {
		$event->log("find_virtualization_host", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Found Virtualization host resource $less_load_resource_id as the target for the new vm ", "", "", 0, 0, 0);
	}
	return $less_load_resource_id;
}




// finds a VM host appliance with the resource in poweroff state
// fitting to the virtualization technology of the appliance resource
// This is to wakeup a host for a VM failover
function find_virtualization_host_appliance_poweroff($v_plugin_name, $start_from_off_timeout) {
	global $event;
	global $OPENQRM_SERVER_IP_ADDRESS;
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_EXEC_PORT;
	global $RESOURCE_INFO_TABLE;
	global $openqrm_server;
	global $RootDir;
	
	$vhost_type = new virtualization();
	$vhost_type->get_instance_by_type($v_plugin_name);
	$event->log("find_virtualization_host_appliance_poweroff", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Trying to find a Virtualizatin Host type  $vhost_type->type $vhost_type->name in poweroff state.", "", "", 0, 0, 0);
	// for all in appliance list, find virtualization host appliances
	$appliance_tmp = new appliance();
	$appliance_id_list = $appliance_tmp->get_all_ids();
	$powerd_off_appliance_list = array();
	foreach($appliance_id_list as $id_arr) {
		foreach($id_arr as $id) {
			$appliance = new appliance();
			$appliance->get_instance_by_id($id);
			// skip auto-resources
			if ($appliance->resources < 0) {
				continue;
			}
			$sfo_resource = new resource();
			$sfo_resource->get_instance_by_id($appliance->resources);
			// poweroff ?
			if ((!strcmp($appliance->state, "stopped")) && (!strcmp($sfo_resource->state, "off"))) {
				if ($appliance->virtualization == $vhost_type->id) {
					// check if the resource can start-from-off
					$can_start_from_off = $sfo_resource->get_resource_capabilities('SFO');
					if ($can_start_from_off == 1) {
						// we have found an active appliance from the right virtualization type
						$powerd_off_appliance_list[] .= $id;
						$event->log("find_virtualization_host_appliance_poweroff", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Adding appliance ".$id." as possible candidate to wakeup.", "", "", 0, 0, 0);
					} else {
						$event->log("find_virtualization_host_appliance_poweroff", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.phpp", "Resource ID ".$sfo_resource->id." cannot start-from-off. Skipping.", "", "", 0, 0, 0);
					}
				}
			}
		}
	}
	// did we found any ?
	if (count($powerd_off_appliance_list) < 1) {
		$event->log("find_virtualization_host_appliance_poweroff", $_SERVER['REQUEST_TIME'], 2, "openqrm-highavailability-ha-hook.php", "There is no virtualization host type ".$vhost_type->name." available to start-from-off!", "", "", 0, 0, 0);
		return 0;
	}
	// simply take the first one
	$in_active_appliance = new appliance();
	foreach($powerd_off_appliance_list as $in_active_id) {
		$in_active_appliance->get_instance_by_id($in_active_id);
		break;
	}
	// simply start the appliance, the rest will be done by the appliance start hook sending power-on
	// monitor until it is fully up or timeout
	$event->log("find_virtualization_host_appliance_poweroff", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Waking up Host appliance $in_active_id, waiting until it is fully active ...", "", "", 0, 0, 0);

	$host_appliance_resource = new resource();
	$host_appliance_resource->get_instance_by_id($in_active_appliance->resources);
	$host_appliance_kernel = new kernel();
	$host_appliance_kernel->get_instance_by_id($in_active_appliance->kernelid);

	$openqrm_server->send_command("openqrm_assign_kernel $host_appliance_resource->id $host_appliance_resource->mac $host_appliance_kernel->name");
	sleep(5);
	$in_active_appliance->start();
	sleep(20);

	// check until it is full up
	$in_active_resource = new resource();
	$sec_loops = 0;
	while (0 == 0) {
		echo " ";
		flush();
		sleep(2);
		$sec_loops++;
		$sec_loops++;
		// check if the resource is active
		$event->log("find_virtualization_host_appliance_poweroff", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Waiting for Host resource id ".$in_active_resource->id." to startup from power-off.", "", "", 0, 0, 0);
		$in_active_resource->get_instance_by_id($in_active_appliance->resources);
		if (!strcmp($in_active_resource->state, "active")) {
			// the host is up :) return the appliance id of the host
			$event->log("find_virtualization_host_appliance_poweroff", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Host resource id ".$in_active_resource->id." successfully started from power-off.", "", "", 0, 0, 0);
			return $in_active_id;
		}
		if ($start_from_off_timeout <= $sec_loops) {
			$event->log("find_virtualization_host_appliance_poweroff", $_SERVER['REQUEST_TIME'], 2, "openqrm-highavailability-ha-hook.php", "Timeout while waiting for resource id ".$in_active_resource->id." to start-from-off.", "", "", 0, 0, 0);
			return 0;
		}
	}
}





// generates a VM compatible mac address
function generate_vm_mac($v_plugin_name) {
	global $event;
	global $vmware_mac_address_space;
	$mac_str = "";
	$mgen_res = new resource();
	$mgen_res->generate_mac();
	// check if we need to generate the additional mac address in the vmware address space
	switch ($v_plugin_name) {
		case 'vmware-esx':
		case 'vmware-server':
		case 'vmware-server2':
			$suggested_mac = $mgen_res->mac;
			$suggested_last_two_bytes = substr($suggested_mac, 12);
			$mac_gen_res_vmw = $vmware_mac_address_space.":".$suggested_last_two_bytes;
			$mac_str = $mac_gen_res_vmw;
			break;
		default:
			$mac_str = $mgen_res->mac;
			break;
	}
	return $mac_str;
}







// this is the HA hook
function openqrm_highavailability_ha_hook($resource_id) {
	global $event;
	global $OPENQRM_SERVER_IP_ADDRESS;
	global $OPENQRM_SERVER_BASE_DIR;
	global $OPENQRM_EXEC_PORT;
	global $RESOURCE_INFO_TABLE;
	global $openqrm_server;
	global $RootDir;
	global $host_start_from_off_timeout;

	$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Handling error event of resource $resource_id", "", "", 0, 0, $resource_id);
	$resource_serves_appliance=0;
	$found_new_resource=0;
	$new_resource_id = 0;
	$create_vm_resource = 0;
	$virtualization_plugin_name = "";
	$create_resource_id = "";
	$create_resource_ip = "";
	$create_resource_mac = "";
	$create_resource_additional_nic_str="";
	$create_resource_swap = "";
	$create_resource_memory = "";
	$create_resource_cpu = "";
	$create_resource_name = "";
	$virtualization_host_resource_id = "";

	// find out if resource serves an appliance
	$appliance = new appliance();
	$appliance_list = array();
	$appliance_list = $appliance->get_all_ids();
	foreach ($appliance_list as $index => $appliance_db) {
		if(strlen($appliance_db["appliance_id"])) {
			$appliance->get_instance_by_id($appliance_db["appliance_id"]);
			// if active
			if ($appliance->stoptime == 0 && $appliance->resources != 0)  {
				if ($appliance->resources == $resource_id) {
					// we found the appliance of the resource !
					$resource_serves_appliance=1;
					break;
				}
			}
		}
	}
	// log ha error, do not handle resources which are not in use for now
	if ($resource_serves_appliance == 0) {
		$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Not handling HA for idle Resource $resource_id.", "", "", 0, 0, $resource_id);
		return;
	}
	// is the appliance HA at all ?
	if ($appliance->highavailable <> 1) {
		$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Appliance $appliance->id is in error but not marked as high-available.", "", "", 0, 0, $resource_id);
		return;
	}

	$ha_appliance_lock_file = "$OPENQRM_SERVER_BASE_DIR/openqrm/plugins/highavailability/lock/ha-lock.".$appliance->id;
	if (file_exists($ha_appliance_lock_file)) {
		$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 2, "openqrm-highavailability-ha-hook.php", "HA for appliance $appliance->id is locked by $ha_appliance_lock_file !", "", "", 0, 0, $resource_id);
		return;
	}

	$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Trying to find a new resource for $appliance->id", "", "", 0, 0, $resource_id);

	// find new idle resource
	$appliance_virtualization=$appliance->virtualization;
	// find_resource will automatically set the resources parameter
	$appliance->find_resource($appliance_virtualization);
	$appliance->get_instance_by_id($appliance->id);

	// no idle resources were found, try to start from of it it is physical
	if ($appliance->resources == $resource_id) {
		// but if there is one offline maybe we can wake one up ?
		$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 2, "openqrm-highavailability-ha-hook.php", "Trying to find a resource to wakeup for appliance $appliance->id !", "", "", 0, 0, $resource_id);
		$virtualization = new virtualization();
		$virtualization->get_instance_by_id($appliance_virtualization);
		// we are searching for physical systems when we want to deploy a virtualization host
		if (strstr($virtualization->name, "Host")) {
			$appliance_virtualization=1;
		}
		// phys. or virtual ?
		if ($appliance_virtualization == 1) {
			// physical
			$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 2, "openqrm-highavailability-ha-hook.php", "Need to find a physical resource for appliance $appliance->id !", "", "", 0, 0, $resource_id);
			$appliance = find_phys_resource_poweroff($appliance);

		} else {
			// if it is a VM maybe we can start one on a Virtualization Host ?
			$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 2, "openqrm-highavailability-ha-hook.php", "Trying to find a Hypervisor to create a new resource for appliance $appliance->id !", "", "", 0, 0, $resource_id);
			$virtualization_plugin_name = str_replace("-vm", "", $virtualization->type);
			// get the vhostid of the resource to provide it as parameter, avoid starting on the same host if possible
			$failed_resource = new resource();
			$failed_resource->get_instance_by_id($resource_id);
			$virtualization_host_resource_id = find_virtualization_host($virtualization_plugin_name, $failed_resource->vhostid);
			if ($virtualization_host_resource_id < 0) {
				// we did not find any online virtualization host
				$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 2, "openqrm-highavailability-ha-hook.php", "There is no active Virtualization hosts available! Trying to wake-up ...", "", "", 0, 0, 0);
				//  try find an stopped virtualization host with a powerd-off resource, then wake up
				// the following function blocks until the appliance/resource is active
				$host_appliance_id_powerd_off = find_virtualization_host_appliance_poweroff($virtualization_plugin_name, $host_start_from_off_timeout);
				if ($host_appliance_id_powerd_off > 0) {
					$appliance_to_wake_up = new appliance();
					$appliance_to_wake_up->get_instance_by_id($host_appliance_id_powerd_off);
					$virtualization_host_resource_id = $appliance_to_wake_up->resources;
					$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Found VM Host appliance ID ".$host_appliance_id_powerd_off." Res. ID ".$virtualization_host_resource_id." and woke it up.", "", "", 0, 0, 0);
				} else {
					$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 2, "openqrm-highavailability-ha-hook.php", "Did not found any powerd-off Virtualization Hosts to wake-up!", "", "", 0, 0, 0);
					return;
				}
			}

			// we found an online virtualization host and prepare to create and start a new vm for fail-over
			// get next resource id
			$create_resource_id = openqrm_db_get_free_id('resource_id', $RESOURCE_INFO_TABLE);
			// create resource in openqrm
			$create_resource_ip="0.0.0.0";
			// check for VMware mac addresses
			$create_resource_mac = generate_vm_mac($virtualization_plugin_name);
			// get some more vm parameters from the old resource
			$old_vm_res = new resource();
			$old_vm_res->get_instance_by_id($resource_id);
			// additional network cards
			if ($old_vm_res->nics > 1) {
				$anic = 1;
				$additional_nics = $old_vm_res->nics -1;
				$create_resource_additional_nic_str="";
				while ($anic < $additional_nics) {
					$new_vm_mac = generate_vm_mac($virtualization_plugin_name);
					switch ($virtualization_plugin_name) {
						# citrix + vbox vms network parameter starts with -m1
						case 'citrix':
						case 'vbox':
							$nic_nr = $anic;
							$create_resource_additional_nic_str .= " -m".$nic_nr." ".$new_vm_mac;
							break;
						# vms network parameter starts with -m2
						default:
							$nic_nr = $anic + 1;
							$create_resource_additional_nic_str .= " -m".$nic_nr." ".$new_vm_mac;
							break;
					}
					$anic++;
				}
			}
			$create_resource_swap = $old_vm_res->swaptotal;
			$create_resource_memory = $old_vm_res->memtotal;
			$create_resource_cpu = $old_vm_res->cpunumber;
			$create_resource_name = "auto-ha-res".$create_resource_id;

			// send command to the openQRM-server
			$openqrm_server->send_command("openqrm_server_add_resource $create_resource_id $create_resource_mac $create_resource_ip");
			// add to openQRM database
			$create_resource_fields["resource_id"]=$create_resource_id;
			$create_resource_fields["resource_ip"]=$create_resource_ip;
			$create_resource_fields["resource_mac"]=$create_resource_mac;
			$create_resource_fields["resource_localboot"]=0;
			$create_resource_fields["resource_vtype"]=$virtualization->id;
			$create_resource_fields["resource_vhostid"]=$virtualization_host_resource_id;
			$create_resource = new resource();
			$create_resource->add($create_resource_fields);

			// plug in the virtualization cloud hook
			$virtualization_ha_hook = "$RootDir/plugins/$virtualization_plugin_name/openqrm-$virtualization_plugin_name-ha-cmd-hook.php";
			if (file_exists($virtualization_ha_hook)) {
				$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Found plugin $virtualization_plugin_name handling to create the VM.", "", "", 0, 0, $create_resource_id);
				require_once "$virtualization_ha_hook";
				$virtualization_method="create_".$virtualization_plugin_name."_vm";
				$virtualization_method=str_replace("-", "_", $virtualization_method);
				$virtualization_method($virtualization_host_resource_id, $create_resource_name, $create_resource_mac, $create_resource_memory, $create_resource_cpu, $create_resource_swap, $create_resource_additional_nic_str);
				// create lockfile for appliance to wait until the vm is up + idle
				$now=$_SERVER['REQUEST_TIME'];
				$ha_lock_fp = fopen($ha_appliance_lock_file, 'w');
				fwrite($ha_lock_fp, $now);
				fclose($ha_lock_fp);


			} else {
				$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Do not know how to create VM from type $virtualization_plugin_name.", "", "", 0, 0, $create_resource_id);
			}

			// here we wait until the new vm is up + idle
			// -> we need to wait because we do not know its ip address yet
			// -> the resource-ip may be used for storage authentication so better to have it up + idle
			$vm_create_timeout = 240;
			$sec_loops = 0;
			while (0 == 0) {
				echo " ";
				flush();
				sleep(2);
				$sec_loops++;
				$sec_loops++;
				// check
				$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 2, "openqrm-highavailability-ha-hook.php", "Waiting for startup of new resource id ".$create_resource_id.".", "", "", 0, 0, 0);
				$create_resource->get_instance_by_id($create_resource_id);
				if (($create_resource->ip != "0.0.0.0") && ($create_resource->state == "active")) {
					// the new vm is up :)
					break;
				}
				if ($vm_create_timeout <= $sec_loops) {
					$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 2, "openqrm-highavailability-ha-hook.php", "Timeout while waiting for new resource id ".$create_resource_id." to start.", "", "", 0, 0, 0);
					return;
				}
			}

			// resource is idle, save new resource_id in appliance
			$appliance_fields = array();
			$appliance_fields['appliance_resources'] = $create_resource_id;
			$appliance->update($appliance->id, $appliance_fields);
			// we remove the lockfile at the very end

		}
	}


	// update the appliance object now
	$appliance->get_instance_by_id($appliance->id);
	// we still have no new resource
	if ($appliance->resources == $resource_id) {
		// in case no resources were found log another ha-error event !
		$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 2, "openqrm-highavailability-ha-hook.php", "Could not find or wakeup a free resource for appliance $appliance->id !", "", "", 0, 0, $resource_id);
		return;
	}

	// save the new id
	$new_resource_id = $appliance->resources;
	// if we found a resource which fits to the appliance we first try to "fence" the old one

	// check for plugins providing a fencing hook
	// -> make sure $resource_id is down
	if ($appliance_virtualization == 1) {
		// physical fencing, check for plugins providing a hook
		$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 2, "openqrm-highavailability-ha-hook.php", "Trying to fence physical resource of appliance $appliance->id !", "", "", 0, 0, $resource_id);
		$plugin = new plugin();
		$enabled_plugins = $plugin->enabled();
		foreach ($enabled_plugins as $index => $plugin_name) {
			$plugin_fence_physical_resource = "$RootDir/plugins/$plugin_name/openqrm-$plugin_name-resource-fence-hook.php";
			if (file_exists($plugin_fence_physical_resource)) {
				$event->log("fence", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Found plugin $plugin_name handling fence-physical-resource event.", "", "", 0, 0, $resource_id);
				require_once "$plugin_fence_physical_resource";
				$resource_fence_function="openqrm_"."$plugin_name"."_fence_resource";
				$resource_fence_function=str_replace("-", "_", $resource_fence_function);
				$resource_fence_function($resource_id);
			}
		}



	} else {
		// if it is a VM maybe we try to stop it hard on its Virtualization Host
		$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 2, "openqrm-highavailability-ha-hook.php", "Trying to fence the virtual resource of appliance $appliance->id !", "", "", 0, 0, $resource_id);

		// fence the vm on its host
		$auto_resource = new resource();
		$auto_resource->get_instance_by_id($resource_id);
		$host_resource = new resource();
		$host_resource->get_instance_by_id($auto_resource->vhostid);
		// find virtualization provider
		$vtype = new virtualization();
		$vtype->get_instance_by_id($auto_resource->vtype);
		$virtualization_plugin_name = str_replace("-vm", "", $vtype->type);

		$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Fencing resource $resource_id type $virtualization_plugin_name on host $host_resource->id", "", "", 0, 0, $resource_id);
		// we need to have an openQRM server object too since some of the
		// virtualization commands are sent from openQRM directly
		$openqrm = new openqrm_server();
		// plug in the virtualization cloud hook
		$virtualization_ha_hook = "$RootDir/plugins/$virtualization_plugin_name/openqrm-$virtualization_plugin_name-ha-cmd-hook.php";
		if (file_exists($virtualization_ha_hook)) {
			$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Found plugin $virtualization_plugin_name handling to fence the VM.", "", "", 0, 0, $resource_id);
			require_once "$virtualization_ha_hook";
			$virtualization_method="fence_".$virtualization_plugin_name."_vm";
			$virtualization_method=str_replace("-", "_", $virtualization_method);
			$virtualization_method($host_resource->id, $auto_resource->mac);
		} else {
			$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Do not know how to fence VM from type $virtualization_plugin_name.", "", "", 0, 0, $resource_id);
		}
	}

	// After fencing we stop the appliance (using the old resource_id, update it and restart it again
	$appliance_fields = array();
	$appliance_fields['appliance_resources'] = $resource_id;
	$appliance->update($appliance->id, $appliance_fields);
	$appliance->get_instance_by_id($appliance->id);
	$appliance->stop();

	// set pxe to idle again
	$old_resource = new resource();
	$old_resource->get_instance_by_id($resource_id);
	$openqrm_server->send_command("openqrm_assign_kernel $old_resource->id $old_resource->mac default");

	// since the image stays the same we just remove all
	// image_authentications for the resource
	$image_authentication = new image_authentication();
	$ia_id_ar = $image_authentication->get_all_ids();
	foreach($ia_id_ar as $ia_list) {
		$ia_auth_id = $ia_list['ia_id'];
		$ia_auth = new image_authentication();
		$ia_auth->get_instance_by_id($ia_auth_id);
		if ($ia_auth->resource_id == $resource_id) {
			$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 5, "openqrm-highavailability-ha-hook.php", "Removing image_authentication $ia_auth_id for resource id $resource_id", "", "", 0, 0, $resource_id);
			$ia_auth->remove($ia_auth_id);
		}
	}

	// prepare restart on other resource
	$appliance_fields = array();
	$appliance_fields['appliance_resources'] = $new_resource_id;
	$appliance->update($appliance->id, $appliance_fields);
	$appliance->get_instance_by_id($appliance->id);
	// set new appliance kernel in pxe config before start
	$new_resource = new resource();
	$new_resource->get_instance_by_id($new_resource_id);
	$kernel = new kernel();
	$kernel->get_instance_by_id($appliance->kernelid);
	$openqrm_server->send_command("openqrm_assign_kernel $new_resource->id $new_resource->mac $kernel->name");
	// update new resources lastgood avoid running the HA hook again
	$reslastgood = $_SERVER['REQUEST_TIME'];
	$resource_fields=array();
	$resource_fields["resource_lastgood"]=$reslastgood;
	$new_resource->update_info($new_resource_id, $resource_fields);
	$appliance->start();
	// :)

	// remove lockfile if existing
	if (file_exists($ha_appliance_lock_file)) {
		$event->log("openqrm_ha_hook", $_SERVER['REQUEST_TIME'], 2, "openqrm-highavailability-ha-hook.php", "Removing lock for appliance $appliance->id - $ha_appliance_lock_file !", "", "", 0, 0, $resource_id);
		unlink($ha_appliance_lock_file);
	}

}



?>


