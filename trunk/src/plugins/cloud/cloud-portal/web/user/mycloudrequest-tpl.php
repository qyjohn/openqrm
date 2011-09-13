<!--
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
-->



<script type="text/javascript">

	window.onload = function() {


		<!-- remove selected ip from the other selects -->
		$("#nic1").change(function() {
			var sid = $("#nic1 option:selected").val();
			var last_sid = $("#nic1 option:last").val();
			if (sid != -2) {
				for (var n=1; n<=last_sid; n++) {
					var sidval = $('#nic2 option:eq('+ n +')').val();
					if (sidval == sid) {
						$('#nic2 option:eq('+ n +')').remove();
					}
					var sidval = $('#nic3 option:eq('+ n +')').val();
					if (sidval == sid) {
						$('#nic3 option:eq('+ n +')').remove();
					}
					var sidval = $('#nic4 option:eq('+ n +')').val();
					if (sidval == sid) {
						$('#nic4 option:eq('+ n +')').remove();
					}
				}
			}
		})


		$("#nic2").change(function() {
			var sid = $("#nic2 option:selected").val();
			var last_sid = $("#nic2 option:last").val();
			if (sid != -2) {
				for (var n=1; n<=last_sid; n++) {
					var sidval = $('#nic1 option:eq('+ n +')').val();
					if (sidval == sid) {
						$('#nic1 option:eq('+ n +')').remove();
					}
					var sidval = $('#nic3 option:eq('+ n +')').val();
					if (sidval == sid) {
						$('#nic3 option:eq('+ n +')').remove();
					}
					var sidval = $('#nic4 option:eq('+ n +')').val();
					if (sidval == sid) {
						$('#nic4 option:eq('+ n +')').remove();
					}
				}
			}
		})


		$("#nic3").change(function() {
			var sid = $("#nic3 option:selected").val();
			var last_sid = $("#nic3 option:last").val();
			if (sid != -2) {
				for (var n=1; n<=last_sid; n++) {
					var sidval = $('#nic1 option:eq('+ n +')').val();
					if (sidval == sid) {
						$('#nic1 option:eq('+ n +')').remove();
					}
					var sidval = $('#nic2 option:eq('+ n +')').val();
					if (sidval == sid) {
						$('#nic2 option:eq('+ n +')').remove();
					}
					var sidval = $('#nic4 option:eq('+ n +')').val();
					if (sidval == sid) {
						$('#nic4 option:eq('+ n +')').remove();
					}
				}
			}
		})


		$("#nic4").change(function() {
			var sid = $("#nic4 option:selected").val();
			var last_sid = $("#nic4 option:last").val();
			if (sid != -2) {
				for (var n=1; n<=last_sid; n++) {
					var sidval = $('#nic2 option:eq('+ n +')').val();
					if (sidval == sid) {
						$('#nic2 option:eq('+ n +')').remove();
					}
					var sidval = $('#nic3 option:eq('+ n +')').val();
					if (sidval == sid) {
						$('#nic3 option:eq('+ n +')').remove();
					}
					var sidval = $('#nic1 option:eq('+ n +')').val();
					if (sidval == sid) {
						$('#nic1 option:eq('+ n +')').remove();
					}
				}
			}
		})


		<!-- adjust selects accoring resource quantity -->
		$("#cr_resource_quantity").change(function() {
			var quantiy = $("#cr_resource_quantity option:selected").val();
			if (quantiy != 1) {
				$('#nic1 option:eq(1)').attr('selected', 'selected');
				$('#nic2 option:eq(1)').attr('selected', 'selected');
				$('#nic3 option:eq(1)').attr('selected', 'selected');
				$('#nic4 option:eq(1)').attr('selected', 'selected');
			} else {
				$('#nic1 option:eq(0)').attr('selected', 'selected');
				$('#nic2 option:eq(0)').attr('selected', 'selected');
				$('#nic3 option:eq(0)').attr('selected', 'selected');
				$('#nic4 option:eq(0)').attr('selected', 'selected');
			}
		})


		<!-- adjust ip selects according to the nic count -->
		$("#cr_network_req").change(function() {
			var network_req = $("#cr_network_req option:selected").val();
			switch (network_req) {
				case '1':
					$('#nic1').removeAttr("disabled"); 
					$('#nic2').attr('disabled', 'true');
					$('#nic3').attr('disabled', 'true');
					$('#nic4').attr('disabled', 'true');
					break;
				case '2':
					$('#nic1').removeAttr("disabled");
					$('#nic2').removeAttr("disabled");
					$('#nic3').attr('disabled', 'true');
					$('#nic4').attr('disabled', 'true');
					break;
				case '3':
					$('#nic1').removeAttr("disabled");
					$('#nic2').removeAttr("disabled");
					$('#nic3').removeAttr("disabled");
					$('#nic4').attr('disabled', 'true');
					break;
				case '4':
					$('#nic1').removeAttr("disabled");
					$('#nic2').removeAttr("disabled");
					$('#nic3').removeAttr("disabled");
					$('#nic4').removeAttr("disabled"); 
					break;
				default:
					$('#nic1').attr('disabled', 'false');
					$('#nic2').attr('disabled', 'false');
					$('#nic3').attr('disabled', 'false');
					$('#nic4').attr('disabled', 'false');
					alert("default");
					break;
			}

		})

		<!-- preset ip selects to 1 nic -->
		$('#nic1').removeAttr("disabled");
		$('#nic2').attr('disabled', 'true');
		$('#nic3').attr('disabled', 'true');
		$('#nic4').attr('disabled', 'true');





};







</script>


<form action="{formaction}">
{currentab}
<h1>申请资源&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<a href="#" onClick="javascript:window.open('vcd/','','location=0,status=0,scrollbars=1,width=910,height=740,left=100,top=50,screenX=100,screenY=50');">使用可视化云构架设计</a></h1>

	 <div id="subtitle">
	{subtitle}
	</div>

	<div id="cloud_request">

	{cloud_user}
	<div id="cr_start_input">
	{cloud_request_start}
	</div>
	<br>
	开始时间：
	<br>
	<div id="cr_stop_input">
	{cloud_request_stop}
	</div>
	<br>
	终止时间：
	<br>
	<hr>
	{cloud_resource_quantity}
	{cloud_resource_type_req}
	{cloud_kernel_id}
	{cloud_image_id}
	{cloud_ram_req}
	{cloud_cpu_req}
	{cloud_disk_req}
	{cloud_network_req}
	{cloud_ha}
	{cloud_clone_on_deploy}

	</div>

	<div id="cloud_applications">
		<div id="puppet">
		<b><u>{cloud_puppet_title}</u></b>
		<br>
		{cloud_show_puppet}
		</div>

		<div id="ip_mgmt">
		<b><u>{cloud_ip_mgmt}</u></b>
		<br>
		{cloud_ip_mgmt_select}
		</div>



	</div>


	<div id="cloud_request_costs">
		<div id="costs">
			<b><u>费用计算</u></b>
			<br>
			<br>
			<u>组件／价格</u>
			<br>
			<ul type="none">
				<li><div id="cost_resource_type_req">虚拟化资源：<div id="cost_resource_type_req_val" class="inline">0</div></div></li>
				<li><div id="cost_kernel">内核：<div id="cost_kernel_val" class="inline">0</div></div></li>
				<li><div id="cost_memory">内存：<div id="cost_memory_val" class="inline">0</div></div></li>
				<li><div id="cost_cpu">处理器：<div id="cost_cpu_val" class="inline">0</div></div></li>
				<li><div id="cost_disk">磁盘：<div id="cost_disk_val" class="inline">0</div></div></li>
				<li><div id="cost_network">网络：<div id="cost_network_val" class="inline">0</div></div></li>
				<li><div id="cost_ha">高可用性：<div id="cost_ha_val" class="inline">0</div></div></li>
				<li><div id="cost_apps">应用：<div id="cost_apps_val" class="inline">0</div></div></li>
			</ul>

			<div id="costs_summary">
				<hr>
				数量： <div id="quantity_val" class="inline">0</div> * <div id="cost_per_appliance_val" class="inline">0</div>
				<hr>
				价格：<div id="cost_overall_val" class="inline">0</div> CCU/h
				<hr>
				<br>
				<br>
				<nobr>1000 CCUs == <div id="cloud_1000_ccus" class="inline">0</div> <div id="cloud_currency" class="inline">0</div></nobr>
				<hr>
				每小时： <div id="cost_hourly" class="inline">0</div> <div id="cloud_currency_h" class="inline">0</div></nobr>
				<br>
				每天： <div id="cost_daily" class="inline">0</div> <div id="cloud_currency_d" class="inline">0</div></nobr>
				<br>
				每月： <div id="cost_monthly" class="inline">0</div> <div id="cloud_currency_m" class="inline">0</div></nobr>

			</div>

		</div>
	</div>


	<div id="cloud_limits">
		<div id="cloud_user_limits">
		<b><u>全局设定</u></b>
		<br>
		<small>（由系统管理员设置）</small>
		<br>
		{cloud_global_limits}
		</div>
		<br>
		<div id="cloud_global_limits">
		<b><u>用户设定</u></b>
		<br>
		<small>（0 = 无限制）</small>
		<br>
		{cloud_user_limits}
		</div>
	</div>



	<div id="submit_request">{submit_save}</div>

	<div id="profile_name_input"> 或者作为资料保存:<br>{profile_name_input}</div>

	<div id="save_profile">{cloud_profile}</div>


</form>


	<script type="text/javascript">

		// check if the cloudselector is enabled, if not hide it
		$.ajax({
				url : "mycloudrequests.php?action=get_cloudselector_state",
				type: "POST",
				cache: false,
				async: false,
				dataType: "html",
				success : function (data) {
					var cloudselector_state = 0;
					cloudselector_state = parseInt(data);
					if (cloudselector_state == 0) {
						$("#cloud_request_costs").hide();
					}
				}
			})



		// resource_type_req
		$("select[name=cr_resource_type_req]").change(function () {
			var res_type = $("select[name=cr_resource_type_req]").val();
			$.ajax({
				url : "mycloudrequests.php?action=get_resource_type_req_cost&res_type=" + res_type,
				type: "POST",
				cache: false,
				async: false,
				dataType: "html",
				success : function (data) {
					$("#cost_resource_type_req_val").text(data);;
				}
			});
			recalculate_costs();
		}).change();
		// kernel
		$("select[name=cr_kernel_id]").change(function () {
			var kernel_id = $("select[name=cr_kernel_id]").val();
			$.ajax({
				url : "mycloudrequests.php?action=get_kernel_cost&kernel_id=" + kernel_id,
				type: "POST",
				cache: false,
				async: false,
				dataType: "html",
				success : function (data) {
					$("#cost_kernel_val").text(data);;
				}
			});
			recalculate_costs();
		}).change();
		// memory
		$("select[name=cr_ram_req]").change(function () {
			var memory_req = $("select[name=cr_ram_req]").val();
			$.ajax({
				url : "mycloudrequests.php?action=get_memory_cost&memory_req=" + memory_req,
				type: "POST",
				cache: false,
				async: false,
				dataType: "html",
				success : function (data) {
					$("#cost_memory_val").text(data);;
				}
			});
			recalculate_costs();
		}).change();
		// cpu
		$("select[name=cr_cpu_req]").change(function () {
			var cpu_req = $("select[name=cr_cpu_req]").val();
			$.ajax({
				url : "mycloudrequests.php?action=get_cpu_cost&cpu_req=" + cpu_req,
				type: "POST",
				cache: false,
				async: false,
				dataType: "html",
				success : function (data) {
					$("#cost_cpu_val").text(data);;
				}
			});
			recalculate_costs();
		}).change();
		// disk
		$("select[name=cr_disk_req]").change(function () {
			var disk_req = $("select[name=cr_disk_req]").val();
			$.ajax({
				url : "mycloudrequests.php?action=get_disk_cost&disk_req=" + disk_req,
				type: "POST",
				cache: false,
				async: false,
				dataType: "html",
				success : function (data) {
					$("#cost_disk_val").text(data);;
				}
			});
			recalculate_costs();
		}).change();
		// network
		$("select[name=cr_network_req]").change(function () {
			var network_req = $("select[name=cr_network_req]").val();
			$.ajax({
				url : "mycloudrequests.php?action=get_network_cost&network_req=" + network_req,
				type: "POST",
				cache: false,
				async: false,
				dataType: "html",
				success : function (data) {
					$("#cost_network_val").text(data);;
				}
			});
			recalculate_costs();
		}).change();
		// ha
		$("input[name=cr_ha_req]").click(function () {

			if ($("input[name=cr_ha_req]").is(":checked")) {
				$.ajax({
					url : "mycloudrequests.php?action=get_ha_cost",
					type: "POST",
					cache: false,
					async: false,
					dataType: "html",
					success : function (data) {
						$("#cost_ha_val").text(data);
					}
				});
			} else {
				$("#cost_ha_val").text("0");
			}
			recalculate_costs();
		});

		// apps
		$("input[name='puppet_groups[]']").each(
			function() {
				$(this).click(function() {
					if($(this).is(":checked")) {
						var application = $(this).val();
						$.ajax({
							url : "mycloudrequests.php?action=get_apps_cost&application=" + application,
							type: "POST",
							cache: false,
							async: false,
							dataType: "html",
							success : function (data) {
								var current_app_cost = 0;
								var new_app_cost = 0;
								current_app_cost = parseInt($("#cost_apps_val").text());
								new_app_cost = current_app_cost + parseInt(data);
								$("#cost_apps_val").text(new_app_cost);
							}
						});
					} else {
						var application = $(this).val();
						$.ajax({
							url : "mycloudrequests.php?action=get_apps_cost&application=" + application,
							type: "POST",
							cache: false,
							async: false,
							dataType: "html",
							success : function (data) {
								var current_app_cost = 0;
								var new_app_cost = 0;
								current_app_cost = parseInt($("#cost_apps_val").text());
								new_app_cost = current_app_cost - parseInt(data);
								$("#cost_apps_val").text(new_app_cost);
							}
						});
					}
					recalculate_costs();
				});
			});



		// resource_quantity
		$("select[name=cr_resource_quantity]").change(function () {
			$("#quantity_val").text($("select[name=cr_resource_quantity]").val());;
			recalculate_costs();
		}).change();


		// get the cloud currency
		$.ajax({
			url : "mycloudrequests.php?action=get_cloud_currency",
			type: "POST",
			cache: false,
			async: false,
			dataType: "html",
			success : function (data) {
				$("#cloud_currency").text(data);
				$("#cloud_currency_h").text(data);
				$("#cloud_currency_d").text(data);
				$("#cloud_currency_m").text(data);
			}
		});

		// get the 1000 CCUs value
		$.ajax({
			url : "mycloudrequests.php?action=get_1000_ccu_value",
			type: "POST",
			cache: false,
			async: false,
			dataType: "html",
			success : function (data) {
				$("#cloud_1000_ccus").text(data);
			}
		});


		function recalculate_costs(){
			var sum_per_appliance = 0;
			var sum_overall = 0;
			sum_per_appliance = sum_per_appliance + parseInt($("#cost_resource_type_req_val").text());
			sum_per_appliance = sum_per_appliance + parseInt($("#cost_kernel_val").text());
			sum_per_appliance = sum_per_appliance + parseInt($("#cost_memory_val").text());
			sum_per_appliance = sum_per_appliance + parseInt($("#cost_cpu_val").text());
			sum_per_appliance = sum_per_appliance + parseInt($("#cost_disk_val").text());
			sum_per_appliance = sum_per_appliance + parseInt($("#cost_network_val").text());
			sum_per_appliance = sum_per_appliance + parseInt($("#cost_ha_val").text());
			sum_per_appliance = sum_per_appliance + parseInt($("#cost_apps_val").text());
			sum_overall = sum_per_appliance * parseInt($("#quantity_val").text());
			$("#cost_per_appliance_val").text(sum_per_appliance);;
			$("#cost_overall_val").text(sum_overall);;
			// cost in real currency
			var one_ccu_cost_in_real_currency = 0;
			var appliance_cost_in_real_currency_per_hour = 0;
			var appliance_cost_in_real_currency_per_day = 0;
			var appliance_cost_in_real_currency_per_month = 0;
			one_ccu_cost_in_real_currency = parseInt($("#cloud_1000_ccus").text()) / 1000;
			appliance_cost_in_real_currency_per_hour = sum_overall * one_ccu_cost_in_real_currency;
			appliance_cost_in_real_currency_per_day = appliance_cost_in_real_currency_per_hour * 24;
			appliance_cost_in_real_currency_per_month = appliance_cost_in_real_currency_per_day * 31;
			$("#cost_hourly").text(appliance_cost_in_real_currency_per_hour.toFixed(2));;
			$("#cost_daily").text(appliance_cost_in_real_currency_per_day.toFixed(2));;
			$("#cost_monthly").text(appliance_cost_in_real_currency_per_month.toFixed(2));;
		}

		// calculate again after all values are loaded
		recalculate_costs();

	</script>

