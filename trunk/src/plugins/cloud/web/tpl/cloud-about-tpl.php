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
	width:750px;
}
</style>

<h1><img border=0 src="/openqrm/base/plugins/cloud/img/plugin.png"> Cloud plugin</h1>
<br>
<b>云服务插件</b>
<br>
OpenQRM云服务插件提供了一个完全自动化的计算资源申请、分配与部署工具。注册用户可以通过一个浏览器界面提交资源请求。根据系统的设定，这些请求可以被手动批准或者是自动批准。
当请求被批准之后，OpenQRM将自动处理资源分配和部署等任务。
<br>
<br>
<b>使用方法</b>
<br>
To setup automatic deployment with the cloud-plugin first the openQRM environment needs
 to be populated with available resources, kernels and server-images.
 The combination of those objects will be the base of the cloud-requests later.

<ul>
<li>
启动一些物理计算资源或者虚拟计算资源
</li><li>
创建一个或者多个存储服务器
</li><li>
在存储服务器上创建一个或者多个服务器映像
</li>
</ul>
<br>

<b>云用户</b>
<br>
可以通过两种方式创建云用户：
<br>
1. 用户访问 http://[openqrm-server-ip]/cloud-portal 进行自助注册。
<br>
2. OpenQRM管理员通过云插件的用户界面直接创建用户。
<br>
<br>


<b>Cloud-Requests</b>
<br>
Cloud-Requests can be submitted to the openQRM Cloud either via the external Cloud-portal by a logged in user or
 on behalf of an existing user in the Cloud-Request manager in the openQRM UI.
<br>
<ul>
<li>
<b>start time</b> - When the requested systems should be available
</li><li>
<b>stop time</b> - When the requested systems are not needed any more
</li><li>
<b>Kernel</b> - Selects the kernel for the requested system
</li><li>
<b>Image</b> - Selects the server-image for the requested system
</li><li>
<b>Resource Type</b> - What kind of system should be deployed (physical or virtual)
</li><li>
<b>Memory</b> - How much memory the requested system should have
</li><li>
<b>CPUs</b> - How many CPUs the requested system should have
</li><li>
<b>Disk</b> - In case of Clone-on-deploy how much disk space should be reserved for the user
</li><li>
<b>Network Cards</b> - How many network-cards (and ip-addresses) should be available
</li><li>
<b>Highavailable</b> - Sets if the requested system should be high-available
</li><li>
<b>Clone-on-deploy</b> - If selected openQRM creates a clone of the selected server-image before deployment
</li>
</ul>
<br>


<b>Cloud Configuration</b>
<br>
Via the Cloud-Config Link in the Cloud-plugin menu the following Cloud configuration can be set :
<ul>
<li>
<b>cloud_admin_email</b> - The email address of the Cloud-Administrator
</li><li>
<b>auto_provision</b> - Can be set to true or false. If set to false requests needs manual approval.
</li><li>
<b>external_portal_url</b> - Can be set to the external Url of the Cloud-portal
</li>
</ul>
<br>

<b>Cloud IpGroups</b>
<br>
The openQRM cloud-plugin provides automatically network-configuration for the external interfaces of the deployed systems.
 To create and populate a Cloud IpGroup please follow the steps below :
<ul>
<li>
Select the Cloud IpGroup link from the cloud-plugin menu
</li><li>
Click on 'Create new Cloud IpGroup' link and fill out the network parameters for the new IpGroup
</li><li>
In the IpGroup overview now select the new created IpGroup and click on the 'load-ips' button
</li><li>
Now put a block of ip-addresses for this IpGroup into the textarea and submit.
</li>
</ul>
<br>

<b>Cloud Admin SOAP-WebService</b>
<br>
To easily integrate with third-party provsion environments the openQRM Cloud provides a SOAP-WebService
 for the <nobreak><a href="soap/index.php">Cloud Administrator</a></nobreak> and the Cloud Users.
<br>
<br>
<b>Cloud Lockfile</b>
<br>
The Cloud creates a lockfile at {cloud_lock_file} to ensure transactions.
<br>
<br>

