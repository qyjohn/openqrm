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
<h1><img border=0 src="/openqrm/base/plugins/nfs-storage/img/volumes.png"> NFS Volumes on storage {storage_name}</h1>
{storage_table}
<br><br><br>
{lun_table}
<br><br>
<form action="{formaction}" method="GET">
{add_export_header}
<div style="float:left;">
{nfs_lun_name}
</div>
{hidden_nfs_storage_id}
<div style="text-align:center;">{submit}</div>
</form>
