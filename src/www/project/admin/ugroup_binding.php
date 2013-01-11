<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('pre.php');
require_once('common/project/UGroupBindingViewer.class.php');

$groupId       = $request->getValidated('group_id', 'GroupId', 0);
$ugroupId      = $request->getValidated('ugroup_id', 'uint', 0);
$sourceProject = $request->getValidated('source_project', 'GroupId', 0);

session_require(array('group' => $groupId, 'admin_flags' => 'A'));

$ugroupUserDao = new UGroupUserDao();
$ugroupManager = new UGroupManager(new UGroupDao());
$ugroupBinding = new UGroupBinding($ugroupUserDao, $ugroupManager);

$ugroupBinding->processRequest($ugroupId, $request);

project_admin_header(
                     array('title' => $GLOBALS['Language']->getText('project_ugroup_binding', 'edit_binding_title'),
                           'group' => $groupId,
                           'help'  => 'UserGroups.html')
                    );

$bindingiewer = new UGroupBindingViewer($ugroupBinding, ProjectManager::instance());
echo $bindingiewer->getHTMLContent($groupId, $ugroupId, $sourceProject);

project_admin_footer(array());

?>