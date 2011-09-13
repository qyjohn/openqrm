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
<h1><img border=0 src="/openqrm/base/plugins/netapp-storage/img/volumes.png"> NetApp-Storage {storage_name}</h1>
{storage_table}

{lun_table}
<br>
<br>
<form action="{formaction}" method="GET">
<h1>Add NetApp iSCSI Lun :</h1>
<div style="float:left;">
{netapp_lun_name}
{netapp_lun_size}
</div>
<div style="float:right;">
	{submit}
</div>
{hidden_netapp_storage_id}
<div style="text-align:center;">
	{netapp_aggr_select}
</div>
</form>

