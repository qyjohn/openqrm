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
<style>
.htmlobject_tab_box {
	width:700px;
}
</style>
<form action="{formaction}" method="GET">

<h1><img border=0 src="/openqrm/base/plugins/kvm/img/manager.png">创建KVM虚拟机</h1>

<div style="border: solid 1px #ccc; padding: 10px 10px 0 10px;">

<h4>在KVM Host {kvm_server_id} 上创建一个新虚拟机</h4>
<div style="float:left;">
{kvm_server_name}

<h4>虚拟机配置</h4>
{kvm_server_cpus}
{kvm_server_mac}
{kvm_server_ram}
{kvm_server_disk}
{kvm_server_swap}
</div>


<div style="float:right;">
    <strong>选择网卡：</strong>
    <div style="border: solid 1px #ccc; padding: 10px 10px 0 10px;">

        <input type="radio" name="kvm_nic_model" value="virtio" checked="checked" /> virtio - 最佳性能（仅针对Linux操作系统）<br>
        <input type="radio" name="kvm_nic_model" value="e1000" /> e1000 - 常用于服务器操作系统<br>
        <input type="radio" name="kvm_nic_model" value="rtl8139" /> rtl8139 - 非常稳定，社区支持广泛<br><br>
    </div>
</div>

{hidden_kvm_server_id}

<div style="clear:both;line-height:0px;">&#160;</div>

<div style="text-align:center;">{submit}</div>
<br>
</div>

</form>

