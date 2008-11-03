<?php 
 /**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Ikram BOUOUD, 2008
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */
require_once('pre.php');
$vGroupId = new Valid_UInt('group_id');
if($vGroupId->validate($group_id)) {
    $group_id = $request->get('group_id');
    header('Content-Disposition: filename=export_permissions.csv');
    header('Content-Type: text/csv');
    require_once('../include/permsVisitor.class.php');
    $visitor = new permsVisitor($group_id);
    $visitor->csvFormatting();
}else {
    exit_no_group();
}
?> 
