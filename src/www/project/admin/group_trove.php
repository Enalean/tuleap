<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * http://sourceforge.net
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
require_once('trove.php');
require_once('www/project/admin/project_admin_utils.php');

require_once('common/include/HTTPRequest.class.php');
$request = HTTPRequest::instance();

$group_id = $request->getValidated('group_id', 'uint', 0);

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

// Check for submission. If so, make changes and redirect
$roots = $request->get('root1');
if ($request->exist('Submit') && is_array($roots)) {
    group_add_history ('changed_trove',"",$group_id);

    foreach ($roots as $root_id => $nop) {
        // check for array, then clear each root node for group
        db_query('DELETE FROM trove_group_link WHERE group_id='.db_ei($group_id)
            .' AND trove_cat_root='. db_ei($root_id));

        for ($i = 1 ; $i <= $GLOBALS['TROVE_MAXPERROOT'] ; $i++) {
            $submitted_category = $request->get('root'. $i);
            if (isset($submitted_category[$root_id]) && $submitted_category[$root_id]) {
                $category_id = $submitted_category[$root_id];
                trove_setnode($group_id, $category_id, $root_id);
            }
        }
    }
    session_redirect('/project/admin/?group_id='.$group_id);
}

project_admin_header(array('title'=>$Language->getText('project_admin_grouptrove','g_trove_info'),'group'=>$group_id));

// LJ New message added to explain that if a Topic category is not there
// LJ put the project unclassified and the Codendi team will create the
// Lj new entry
//
print '<P>'.$Language->getText('project_admin_grouptrove','select_3_classifs',$GLOBALS['sys_name']);

print "\n<FORM method=\"post\">";

// HTML select for all available categories for this group
print trove_get_html_allcat_selectfull($group_id);

print '<P><INPUT type="submit" name="Submit" value="'.$Language->getText('project_admin_grouptrove','submit_all_changes').'">';
print '</FORM>';

project_admin_footer(array());
?>
