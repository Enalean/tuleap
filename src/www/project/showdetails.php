<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Project\Admin\Reference\ReferenceAdministrationViews;

require_once __DIR__ . '/../include/pre.php';

$hp = Codendi_HTMLPurifier::instance();

function getReferenceRow($ref, $row_num)
{
    $html = '';

    if ($ref->isActive() && $ref->getId() != 100) {
        $purifier = Codendi_HTMLPurifier::instance();
        $html .= '<TR class="' . util_get_alt_row_color($row_num) . '">';
        $html .= '<TD>' . $purifier->purify($ref->getKeyword()) . '</TD>';
        $html .= '<TD>' . $purifier->purify(ReferenceAdministrationViews::getReferenceDescription($ref)) . '</TD>';
        $html .= '<TD>' . $purifier->purify($ref->getLink()) . '</TD>';
        $html .= '</TR>';
    }

    return $html;
}

function getReferencesTable($groupId)
{
    $html = '';
    $html .= '<h3>' . $GLOBALS['Language']->getText('project_showdetails', 'references') . '</h3>';

    $title_arr[] = $GLOBALS['Language']->getText('project_reference', 'r_keyword');
    $title_arr[] = $GLOBALS['Language']->getText('project_reference', 'r_desc');
    $title_arr[] = $GLOBALS['Language']->getText('project_reference', 'r_link');
    $html .= html_build_list_table_top($title_arr, false, false, true);

    $referenceManager = ReferenceManager::instance();
    $references = $referenceManager->getReferencesByGroupId($groupId); // References are sorted by scope first
    $row_num = 0;
    foreach ($references as $ref) {
        $html .= getReferenceRow($ref, $row_num);
        $row_num++;
    }

    $html .= '</table>';
    return $html;
}

// Check if group_id is valid
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
}

$currentproject = new Project($group_id);

site_project_header(array('title' => $Language->getText('project_showdetails', 'proj_details'),'group' => $group_id,'toptab' => 'summary'));

print '<P><h3>' . $Language->getText('project_showdetails', 'proj_details') . '</h3>';

// Now fetch the project details

$currentproject->displayProjectsDescFieldsValue();

echo getReferencesTable($group_id);

print '<P><a href="/project/?group_id=' . $group_id . '"> ' . $Language->getText('project_showdetails', 'back_main') . ' </a>';

site_project_footer(array());
