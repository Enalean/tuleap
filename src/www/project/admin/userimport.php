<?php

/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mohamed CHAARI, 2006. STMicroelectronics.
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
 */


require_once('pre.php');
require_once('www/project/admin/project_admin_utils.php');
require('www/project/admin/include/UserImportHtml.class');

$Language->loadLanguageMsg('project/project');

if ( !user_isloggedin()) {
    exit_not_logged_in();
    return;
}

if (!isset($_REQUEST['group_id'])) {
  exit_no_group();
}

session_require(array('group'=>$_REQUEST['group_id'],'admin_flags'=>'A'));

project_admin_header(array('title'=>$Language->getText('project_admin_userimport','import_members'),
			     'help' => 'UserImport.html'));
			     
$import = new UserImportHtml($_REQUEST['group_id']);
if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == "parse") {
    $import->displayParse($user_filename);
} else if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == "import") {
    $import->displayImport($parsed_users);    
} else if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == "showformat") {
    $import->displayShowFormat();
} else {
    $import->displayInput();
}

project_admin_footer(array());
  
?>