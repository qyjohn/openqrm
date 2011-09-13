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

<div style="float:left;">

<h1><img border=0 src="/openqrm/base/plugins/kvm/img/plugin.png">KVM插件</h1>
<strong>本插件在KVM版本kvm-62下测试通过测试。</strong>
<br>
<strong>如果您需要KVM所提供的virtio特性，您需要kvm-84以及更高版本的KVM。</strong>
<br>
<br>
KVM插件为CloudPro提供了基于KVM的虚拟化支持。所有KVM Host类别的期间，都会在本插件的VM管理器里列出，并可通过VM管理器的图形用户界面进行管理。
除了创建、启动、关闭、销毁等常用的虚拟机生命周期管理功能之外，本插件还提供了一个对虚拟机进行重新配置（例如增加一个虚拟网卡或者是新的硬盘）的界面。

<br>
<br>
提示：
<br>
CloudPro服务器本身也可以作为KVM Host器件使用。如果您需要这么做的话，您需要在安装CloudPro服务器之前配置好网桥。
您至少需要一个内部网桥作为CloudPro的管理网络，该网桥的名称可以通过KVM插件配置文件中的OPENQRM_PLUGIN_KVM_INTERNAL_BRIDGE参数进行配置。
<br>
<br>
额外的外部网桥（例如接入Internet的网桥）可以通过KVM插件配置文件中的OPENQRM_PLUGIN_KVM_EXTERNAL_BRIDGE参数进行配置。
<br>
CloudPro自动地将所有KVM虚拟机的第一个网卡连接到内部网桥，额外的网卡均连接到外部网桥。这种配置保证了所有的虚拟机都通过第一个网卡连接到CloudPro管理网络，这些虚拟机能够通过PXE从网络启动。
<br>
<br>
配置好网桥之后，CloudPro应该被安装到内部网桥（缺省为br0）上。您可以在启动CloudPro之前，修改/usr/share/openqrm/etc/openqrm-server.conf，将CloudPro的管理网络界面
设置为br0。
<br>
<br>
<br>
<b>使用方法：</b>
<br>

<ul>
<li>
创建一个器件，将其资源种类设置为KVM Host。
</li><li>
在KVM插件的“VM管理器”中选择一个KVM Host，然后创建新的KVM虚拟机。
</li><li>
新创建的KVM虚拟机通过网络启动，成为CloudPro中的一个计算资源。
</li>
</ul>
<br>
<br>

<b>在线迁移：</b>
<br>
<ul>
<li>
如果您需要进行虚拟机的在线迁移，被迁移的虚拟机的config和swap文件需要放置在被KVM主机所共享的存储上（缺省位置为/var/lib/kvm/openqrm）。
</li>
</ul>
<br>
<br>
<hr>
<br>

</div>
