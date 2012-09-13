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
require_once('common/project/UGroupBinding.class.php');

$groupId  = $request->getValidated('group_id', 'GroupId', 0);
$ugroupId = $request->getValidated('ugroup_id', 'uint', 0);

session_require(array('group' => $groupId, 'admin_flags' => 'A'));

$ugroupBinding = new UGroupBinding();

$ugroupBinding->processRequest($ugroupId, $request);

// @TODO: i18n
project_admin_header(array('title' => 'Edit ugroup binding',
                           'group' => $groupId,
                           'help'  => 'UserGroups.html'));

echo $ugroupBinding->getHTMLContent($ugroupId);

project_admin_footer(array());

?>