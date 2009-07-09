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
$vExport = new Valid_WhiteList('export', array('format','csv'));
if($vGroupId->validate($group_id) && $request->valid($vExport) ) {
    $group_id = $request->get('group_id');
    $export   = $request->get('export');
    if (!UserManager::instance()->getCurrentUser()->isMember($group_id, 'A')) {
        $feedback .= $Language->getText('plugin_eac','error_not_admin');
        exit_not_logged_in();
    }
    require_once('../include/permsVisitor.class.php');
    $visitor = new permsVisitor($group_id);        	
    if ($export == 'csv') {
        $visitor->render();
    } else { // export = format
        $visitor->renderDefinitionFormat();
    }
}else {
    exit_no_group();
}
?> 
