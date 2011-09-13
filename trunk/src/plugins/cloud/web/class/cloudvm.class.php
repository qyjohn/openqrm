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

    Copyright 2010, Matthias Rechenburg <matt@openqrm.com>
*/


// This class represents a virtual machine in the cloud of openQRM

$RootDir = $_SERVER["DOCUMENT_ROOT"].'/openqrm/base/';
require_once "$RootDir/include/openqrm-database-functions.php";
require_once "$RootDir/class/resource.class.php";
require_once "$RootDir/class/appliance.class.php";
require_once "$RootDir/class/virtualization.class.php";
require_once "$RootDir/class/openqrm_server.class.php";
require_once "$RootDir/class/plugin.class.php";
require_once "$RootDir/class/event.class.php";
// special cloud classes
require_once "$RootDir/plugins/cloud/class/clouduser.class.php";
require_once "$RootDir/plugins/cloud/class/cloudusergroup.class.php";
require_once "$RootDir/plugins/cloud/class/cloudconfig.class.php";
require_once "$RootDir/plugins/cloud/class/cloudappliance.class.php";
require_once "$RootDir/plugins/cloud/class/cloudrespool.class.php";
require_once "$RootDir/plugins/cloud/class/cloudhostlimit.class.php";
require_once "$RootDir/plugins/cloud/class/cloudhoststartfromoff.class.php";

$event = new event();
global $event;

global $OPENQRM_SERVER_BASE_DIR;
$openqrm_server = new openqrm_server();
$OPENQRM_SERVER_IP_ADDRESS=$openqrm_server->get_ip_address();
global $OPENQRM_SERVER_IP_ADDRESS;
global $RESOURCE_INFO_TABLE;

$vmware_mac_address_space = "00:50:56:20";
global $vmware_mac_address_space;

// timout for starting a host from power-off
$host_start_from_off_timeout=240;
global $host_start_from_off_timeout;



class cloudvm {

	var $resource_id = '';
	var $timeout = '';


	function init($timeout) {
		$this->resource_id=0;
		$this->timeout=$timeout;
	}

	// ---------------------------------------------------------------------------------
	// general cloudvm methods
	// ---------------------------------------------------------------------------------


	// creates a vm from a specificed virtualization type + parameters
	function create($cu_id, $virtualization_type, $name, $mac, $additional_nics, $cpu, $memory, $disk, $timeout) {
		global $OPENQRM_SERVER_BASE_DIR;
		global $OPENQRM_SERVER_IP_ADDRESS;
		global $OPENQRM_EXEC_PORT;
		global $RESOURCE_INFO_TABLE;
		global $vmware_mac_address_space;
		global $host_start_from_off_timeout;
		global $RootDir;
		$this->init($timeout);
		global $event;
		$vtype = new virtualization();
		$vtype->get_instance_by_id($virtualization_type);
		$virtualization_plugin_name = str_replace("-vm", "", $vtype->type);

		$event->log("create", $_SERVER['REQUEST_TIME'], 5, "cloudvm.class.php", "Trying to create new vm type $virtualization_type ($virtualization_plugin_name) $mac/$cpu/$memory/$disk", "", "", 0, 0, 0);
		// here we need to find out if we have a virtualization host providing the type of vms as requested

		// find out the host virtualization type via the plugin name
		$vhost_type = new virtualization();
		$vhost_type->get_instance_by_type($virtualization_plugin_name);
		$event->log("create", $_SERVER['REQUEST_TIME'], 5, "cloudvm.class.php", "Trying to find a virtualization host from type $vhost_type->type $vhost_type->name", "", "", 0, 0, 0);

		// check if resource-pooling is enabled
		$cp_conf = new cloudconfig();
		$show_resource_pools = $cp_conf->get_value(25);	// resource_pools enabled ?

		// for all in appliance list, find virtualization host appliances
		$appliance_tmp = new appliance();
		$appliance_id_list = $appliance_tmp->get_all_ids();
		$active_appliance_list = array();
		$active_appliance_resource_list = array();
		foreach($appliance_id_list as $id_arr) {
			foreach($id_arr as $id) {
				$appliance = new appliance();
				$appliance->get_instance_by_id($id);
				// active ?
				if ($appliance->stoptime == 0 || $appliance->resources == 0) {
					if ($appliance->virtualization == $vhost_type->id) {
						// we have found an active appliance from the right virtualization type
						// Now we check that its resource is active and not in error
						$cvm_resource = new resource();
						$cvm_resource->get_instance_by_id($appliance->resources);
						if (strcmp($cvm_resource->state, "active")) {
							$event->log("create", $_SERVER['REQUEST_TIME'], 2, "cloudvm.class.php", "Appliance $id resource $appliance->resources is not yet active or in error", "", "", 0, 0, $appliance->resources);
							continue;
						}
						// here we check if there is still enough space
						// to create the new vm -> max_vm setting per resource
						$res_hostlimit = new cloudhostlimit();
						$res_hostlimit->get_instance_by_resource($appliance->resources);
						if (strlen($res_hostlimit->id)) {
							if ($res_hostlimit->max_vms >= 0) {
								$new_current_vms = $res_hostlimit->current_vms + 1;
								if ($new_current_vms > $res_hostlimit->max_vms) {
									$event->log("create", $_SERVER['REQUEST_TIME'], 2, "cloudvm.class.php", "Hostlimit max_vm is reached for resource $appliance->resources", "", "", 0, 0, $appliance->resources);
									continue;
								}
							}
						}
						// resource pooling enabled ?
						if (strcmp($show_resource_pools, "true")) {
							// disabled, add any appliance from the right virtualization type
							$active_appliance_list[] .= $id;
							$active_appliance_resource_list[] .= $appliance->resources;
							//$event->log("create", $_SERVER['REQUEST_TIME'], 2, "cloudvm.class.php", "------- resource pooling is disabled", "", "", 0, 0, 0);
						} else {
							//$event->log("create", $_SERVER['REQUEST_TIME'], 2, "cloudvm.class.php", "------- resource pooling is enabled $appliance->resources", "", "", 0, 0, 0);
							// resource pooling enabled, check to which user group the resource belongs to
							$private_resource = new cloudrespool();
							$private_resource->get_instance_by_resource($appliance->resources);
							// is this resource configured in the resource pools ?
							//$event->log("create", $_SERVER['REQUEST_TIME'], 2, "cloudvm.class.php", "------- resource pool id $private_resource->id !", "", "", 0, 0, 0);
							if (strlen($private_resource->id)) {
								//$event->log("create", $_SERVER['REQUEST_TIME'], 2, "cloudvm.class.php", "------- resource $appliance->resources is in a resource pool", "", "", 0, 0, 0);
								// is it hidden ?
								if ($private_resource->cg_id >= 0) {
									//$event->log("create", $_SERVER['REQUEST_TIME'], 2, "cloudvm.class.php", "------- resource $appliance->resources is also configured in resource pool (not hidden)", "", "", 0, 0, 0);
									$cloud_user = new clouduser();
									$cloud_user->get_instance_by_id($cu_id);
									$cloud_user_group = new cloudusergroup();
									$cloud_user_group->get_instance_by_id($cloud_user->cg_id);
									//$event->log("create", $_SERVER['REQUEST_TIME'], 2, "cloudvm.class.php", "------- we have found the users group $cloud_user_group->id", "", "", 0, 0, 0);
									// does it really belongs to the users group ?
									if ($private_resource->cg_id == $cloud_user_group->id) {
										// resource belongs to the users group, add appliance to list
										//$event->log("create", $_SERVER['REQUEST_TIME'], 2, "cloudvm.class.php", "------- adding appliance $id   ", "", "", 0, 0, 0);
										$active_appliance_list[] .= $id;
										$active_appliance_resource_list[] .= $appliance->resources;
									//} else {
									//    $event->log("create", $_SERVER['REQUEST_TIME'], 2, "cloudvm.class.php", "Appliance $id (resource $appliance->resources) is NOT in dedicated for the users group", "", "", 0, 0, 0);
									}
								//} else {
								//    $event->log("create", $_SERVER['REQUEST_TIME'], 2, "cloudvm.class.php", "Appliance $id (resource $appliance->resources) is marked as hidden", "", "", 0, 0, 0);
								}
							}
						}
					}
				}
			}
		}

		// did we found any active host ?
		if (count($active_appliance_list) < 1) {
			$event->log("create", $_SERVER['REQUEST_TIME'], 2, "cloudvm.class.php", "Warning ! There is no active virtualization host type $vhost_type->name available to bring up a new vm", "", "", 0, 0, 0);
			$event->log("create", $_SERVER['REQUEST_TIME'], 2, "cloudvm.class.php", "Notice : Trying to find a Host which can start-from-off .....", "", "", 0, 0, 0);
			// if this method finds a host it will block until the host is up + active
			$cloud_host_start_from_off = new cloudhoststartfromoff();
			$start_from_off_appliance_id = $cloud_host_start_from_off->find_host_to_start_from_off($vhost_type->id, $show_resource_pools, $cu_id, $host_start_from_off_timeout);
			if ($start_from_off_appliance_id > 0) {
				//$event->log("create", $_SERVER['REQUEST_TIME'], 2, "cloudvm.class.php", "------- adding appliance $id   ", "", "", 0, 0, 0);
				$active_appliance_list[] .= $start_from_off_appliance_id;
				// add to active resource list
				$start_from_off_appliance = new appliance();
				$start_from_off_appliance->get_instance_by_id($start_from_off_appliance_id);
				$active_appliance_resource_list[] .= $start_from_off_appliance->resources;

			} else {
				// here we did not found any host to start-from-off
				$event->log("create", $_SERVER['REQUEST_TIME'], 2, "cloudvm.class.php", "Warning ! Could not find any virtualization host type $vhost_type->name to start-from-off", "", "", 0, 0, 0);
				$event->log("create", $_SERVER['REQUEST_TIME'], 2, "cloudvm.class.php", "Warning ! Giving up trying to start a new vm type $vhost_type->name", "", "", 0, 0, 0);
				return false;
			}
		}


		// ! for all virt-storage plugins we need to make sure the vm is created on
		// ! the same host as the image is located, for all others we try to lb
		$less_load_resource_id=-1;
		switch ($virtualization_plugin_name) {
			case 'kvm-storage':
			case 'lxc-storage':
			case 'openvz-storage':
			case 'xen-storage':
				$origin_appliance = new appliance();
				$origin_appliance->get_instance_by_name($name);
				// if we have a cloudappliance already this create is coming from unpause
				// The host to create the new vm on must be the image storage resource
				$vstorage_cloud_app = new cloudappliance();
				$vstorage_cloud_app->get_instance_by_appliance_id($origin_appliance->id);
				if (strlen($vstorage_cloud_app->id)) {
					$vstorage_image = new image();
					$vstorage_image->get_instance_by_id($origin_appliance->imageid);
					$vstorage = new storage();
					$vstorage->get_instance_by_id($vstorage_image->storageid);
					$vstorage_host_res_id = $vstorage->resource_id;
					// check if the origin host is in the active appliances we have found
					if (in_array($vstorage_host_res_id, $active_appliance_resource_list)) {
						$event->log("create", $_SERVER['REQUEST_TIME'], 5, "cloudvm.class.php", "Origin host $vstorage_host_res_id is active. Creating the new vm", "", "", 0, 0, 0);
						$resource = new resource();
						$resource->get_instance_by_id($vstorage_host_res_id);
						$less_load_resource_id = $vstorage_host_res_id;
					} else {
						$event->log("create", $_SERVER['REQUEST_TIME'], 2, "cloudvm.class.php", "Origin host $vstorage_host_res_id is not active. Not creating the new vm", "", "", 0, 0, 0);
					}
				} else {
					// if we do not have a cloudappliance yet we can (should) loadbalance the create vm request
					$event->log("create", $_SERVER['REQUEST_TIME'], 5, "cloudvm.class.php", "Loadbalancing request for creating the new vm", "", "", 0, 0, 0);
					$max_resourc_load = 100;
					foreach($active_appliance_list as $active_id) {
						$active_appliance = new appliance();
						$active_appliance->get_instance_by_id($active_id);
						$resource = new resource();
						$resource->get_instance_by_id($active_appliance->resources);
						if ($resource->load < $max_resourc_load) {
							$max_resourc_load = $resource->load;
							$less_load_resource_id = $resource->id;
							// the cloud-deployment hook of the virt-storage plugin will adapt the image storage id to the host id
						}
					}
				}
				break;

			default:
				// find the appliance with the most less load on it
				$max_resourc_load = 100;
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
				break;
		}


		if ($less_load_resource_id >= 0) {
			$event->log("create", $_SERVER['REQUEST_TIME'], 5, "cloudvm.class.php", "Found Virtualization host resource $less_load_resource_id as the target for the new vm ", "", "", 0, 0, 0);
		}
		// additional network cards
		if ($additional_nics > 0) {
			$anic = 1;
			$additional_nic_str="";
			$mac_gen_res = new resource();
			while ($anic <= $additional_nics) {
				$mac_gen_res->generate_mac();
				// check if we need to generate the additional mac address in the vmware address space
				switch ($virtualization_plugin_name) {
					# vmware vms need to get special macs
					# vms network parameter starts with -m2
					case 'vmware-esx':
					case 'vmware-server':
					case 'vmware-server2':
						$nic_nr = $anic + 1;
						$suggested_mac = $mac_gen_res->mac;
						$suggested_last_two_bytes = substr($suggested_mac, 12);
						$mac_gen_res_vmw = $vmware_mac_address_space.":".$suggested_last_two_bytes;
						$additional_nic_str .= " -m".$nic_nr." ".$mac_gen_res_vmw;
						break;
					# citrix + vbox vms network parameter starts with -m1
					case 'citrix':
					case 'vbox':
						$nic_nr = $anic;
						$additional_nic_str .= " -m".$nic_nr." ".$mac_gen_res->mac;
						break;
					# vms network parameter starts with -m2
					default:
						$nic_nr = $anic + 1;
						$additional_nic_str .= " -m".$nic_nr." ".$mac_gen_res->mac;
						break;
				}
				$anic++;
			}
		}
		// swap, for the cloud vms we simply calculate memory * 2
		$swap = $memory*2;

		// start the vm on the appliance resource
		$host_resource = new resource();
		$host_resource->get_instance_by_id($less_load_resource_id);
		$host_resource_ip = $host_resource->ip;
		// we need to have an openQRM server object too since some of the
		// virtualization commands are sent from openQRM directly
		$openqrm = new openqrm_server();

		// create the new resource + setting the virtualization type
		$vm_resource_ip="0.0.0.0";
		// add to openQRM database
		$vm_resource_fields["resource_ip"]=$vm_resource_ip;
		$vm_resource_fields["resource_mac"]=$mac;
		$vm_resource_fields["resource_localboot"]=0;
		$vm_resource_fields["resource_vtype"]=$vtype->id;
		$vm_resource_fields["resource_vhostid"]=$less_load_resource_id;
		// get the new resource id from the db
		$new_resource_id=openqrm_db_get_free_id('resource_id', $RESOURCE_INFO_TABLE);
		$vm_resource_fields["resource_id"]=$new_resource_id;
		$resource->add($vm_resource_fields);
		// send new-resource command now after the resource is created logically
		$openqrm->send_command("openqrm_server_add_resource $new_resource_id $mac $vm_resource_ip");
		// let the new resource commands settle
		sleep(10);
		// plug in the virtualization cloud hook
		$virtualization_cloud_hook = "$RootDir/plugins/$virtualization_plugin_name/openqrm-$virtualization_plugin_name-cloud-hook.php";
		if (file_exists($virtualization_cloud_hook)) {
			$event->log("create", $_SERVER['REQUEST_TIME'], 5, "cloudvm.class", "Found plugin $virtualization_plugin_name handling to create the VM.", "", "", 0, 0, $resource->id);
			require_once "$virtualization_cloud_hook";
			$virtualization_method="create_".$virtualization_plugin_name."_vm";
			$virtualization_method=str_replace("-", "_", $virtualization_method);
			$virtualization_method($less_load_resource_id, $name, $mac, $memory, $cpu, $swap, $additional_nic_str);
		} else {
			$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloudvm.class", "Do not know how to create VM from type $virtualization_plugin_name.", "", "", 0, 0, 0);
			return false;
		}

		// update hostlimits quite early to avoid overloading a Host with non-starting vms
		// add or update hostlimits
		$res_hostlimit = new cloudhostlimit();
		$res_hostlimit->get_instance_by_resource($host_resource->id);
		if (strlen($res_hostlimit->id)) {
			// update
			$current_vms = $res_hostlimit->current_vms + 1;
			$cloud_hostlimit_fields["hl_current_vms"] = $current_vms;
			$res_hostlimit->update($res_hostlimit->id, $cloud_hostlimit_fields);
		} else {
			// add
			$cloud_hostlimit_fields["hl_id"]=openqrm_db_get_free_id('hl_id', $res_hostlimit->_db_table);
			$cloud_hostlimit_fields["hl_resource_id"] = $host_resource->id;
			$cloud_hostlimit_fields["hl_max_vms"] = -1;
			$cloud_hostlimit_fields["hl_current_vms"] = 1;
			$res_hostlimit->add($cloud_hostlimit_fields);
		}

		$event->log("create", $_SERVER['REQUEST_TIME'], 5, "cloudvm.class.php", "New vm created with resource id ".$new_resource_id." and started. Waiting now until it is active/idle", "", "", 0, 0, 0);
		// setting this object resource id as return state
		$this->resource_id = $new_resource_id;
	}



	// removes a vm from a specificed virtualization type + parameters
	function remove($resource_id, $virtualization_type, $name, $mac) {
		global $OPENQRM_SERVER_BASE_DIR;
		global $OPENQRM_SERVER_IP_ADDRESS;
		global $OPENQRM_EXEC_PORT;
		global $RESOURCE_INFO_TABLE;
		global $RootDir;
		global $event;
		$vtype = new virtualization();
		$vtype->get_instance_by_id($virtualization_type);
		$virtualization_plugin_name = str_replace("-vm", "", $vtype->type);
		// remove the vm from host
		$auto_resource = new resource();
		$auto_resource->get_instance_by_id($resource_id);
		$host_resource = new resource();
		$host_resource->get_instance_by_id($auto_resource->vhostid);
		$event->log("remove", $_SERVER['REQUEST_TIME'], 5, "cloudvm.class.php", "Trying to remove resource $resource_id type $virtualization_plugin_name on host $host_resource->id ($mac)", "", "", 0, 0, 0);
		// we need to have an openQRM server object too since some of the
		// virtualization commands are sent from openQRM directly
		$openqrm = new openqrm_server();
		// plug in the virtualization cloud hook
		$virtualization_cloud_hook = "$RootDir/plugins/$virtualization_plugin_name/openqrm-$virtualization_plugin_name-cloud-hook.php";
		if (file_exists($virtualization_cloud_hook)) {
			$event->log("remove", $_SERVER['REQUEST_TIME'], 5, "cloudvm.class", "Found plugin $virtualization_plugin_name handling to remove the VM.", "", "", 0, 0, $resource_id);
			require_once "$virtualization_cloud_hook";
			$virtualization_method="remove_".$virtualization_plugin_name."_vm";
			$virtualization_method=str_replace("-", "_", $virtualization_method);
			$virtualization_method($host_resource->id, $name, $mac);
		} else {
			$event->log("cloud", $_SERVER['REQUEST_TIME'], 5, "cloudvm.class", "Do not know how to remove VM from type $virtualization_plugin_name.", "", "", 0, 0, 0);
			return false;
		}

		// remove vm from hostlimit current_vms
		$res_hostlimit = new cloudhostlimit();
		$res_hostlimit->get_instance_by_resource($host_resource->id);
		if (strlen($res_hostlimit->id)) {
			if ($res_hostlimit->current_vms > 0) {
				$current_vms = $res_hostlimit->current_vms - 1;
				$cloud_hostlimit_fields["hl_current_vms"] = $current_vms;
				$res_hostlimit->update($res_hostlimit->id, $cloud_hostlimit_fields);
			}
		}

		// resource object remove
		$auto_resource->remove($auto_resource->id, $auto_resource->mac);
		$event->log("remove", $_SERVER['REQUEST_TIME'], 5, "cloudvm.class.php", "Removed resource $resource_id", "", "", 0, 0, 0);

	}



// ---------------------------------------------------------------------------------

}

?>