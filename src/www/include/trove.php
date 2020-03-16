<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
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

// ################################## Trove Globals


// adds a group to a trove node
function trove_setnode($group_id, $trove_cat_id, $rootnode = 0)
{
    // verify we were passed information
    if ((!$group_id) || (!$trove_cat_id)) {
        return 1;
    }

    // verify trove category exists
    $res_verifycat = db_query('SELECT trove_cat_id,fullpath_ids FROM trove_cat WHERE '
    . 'trove_cat_id=' . db_ei($trove_cat_id));
    if (db_numrows($res_verifycat) != 1) {
        return 1;
    }
    $row_verifycat = db_fetch_array($res_verifycat);

    // if we didnt get a rootnode, find it
    if (!$rootnode) {
        $rootnode = trove_getrootcat($trove_cat_id);
    }

    // must first make sure that this is not a subnode of anything current
    $res_topnodes = db_query('SELECT trove_cat.trove_cat_id AS trove_cat_id,'
    . 'trove_cat.fullpath_ids AS fullpath_ids FROM trove_cat,trove_group_link '
    . 'WHERE trove_cat.trove_cat_id=trove_group_link.trove_cat_id AND '
    . 'trove_group_link.group_id=' . db_ei($group_id) . ' AND '
    . 'trove_cat.root_parent=' . db_ei($rootnode));
    while ($row_topnodes = db_fetch_array($res_topnodes)) {
        $pathids = explode(' :: ', $row_topnodes['fullpath_ids']);
        for ($i = 0; $i < count($pathids); $i++) {
         // anything here will invalidate this setnode
            if ($pathids[$i] == $trove_cat_id) {
                return 1;
            }
        }
    }

    // need to see if this one is more specific than another
    // if so, delete the other and proceed with this insertion
    $subnodeids = explode(' :: ', $row_verifycat['fullpath_ids']);
    $res_checksubs = db_query('SELECT trove_cat_id FROM trove_group_link WHERE '
    . 'group_id=' . db_ei($group_id) . ' AND trove_cat_root=' . db_ei($rootnode));
    while ($row_checksubs = db_fetch_array($res_checksubs)) {
     // check against all subnodeids
        for ($i = 0; $i < count($subnodeids); $i++) {
            if ($subnodeids[$i] == $row_checksubs['trove_cat_id']) {
                // then delete subnode
                db_query('DELETE FROM trove_group_link WHERE '
                 . 'group_id=' . db_ei($group_id) . ' AND trove_cat_id='
                 . db_ei($subnodeids[$i]));
            }
        }
    }

    // if we got this far, must be ok
    db_query('INSERT INTO trove_group_link (trove_cat_id,trove_cat_version,'
    . 'group_id,trove_cat_root) VALUES (' . db_ei($trove_cat_id) . ','
    . time() . ',' . db_ei($group_id) . ',' . db_ei($rootnode) . ')');
    return 0;
}

function trove_getrootcat($trove_cat_id)
{
    $parent = 1;
    $current_cat = $trove_cat_id;

    while ($parent > 0) {
        $res_par = db_query("SELECT parent FROM trove_cat WHERE "
               . "trove_cat_id=" . db_ei($current_cat));
        $row_par = db_fetch_array($res_par);
        $parent = $row_par["parent"];
        if ($parent == 0) {
            return $current_cat;
        }
        $current_cat = $parent;
    }

    return 0;
}

// return boolean true when project is categorized
function trove_project_categorized($group_id)
{
    $res_trovecat = db_query('SELECT NULL '
        . 'FROM trove_cat,trove_group_link '
        . 'WHERE trove_cat.trove_cat_id=trove_group_link.trove_cat_id '
        . 'AND trove_group_link.group_id=' . db_ei($group_id));
    if (db_numrows($res_trovecat) < 1) {
        return false;
    } else {
        return true;
    }
}

// returns a full path for a trove category
function trove_getfullpath($node)
{
    $currentcat = $node;
    $first = 1;
    $return = '';

    while ($currentcat > 0) {
        $res = db_query('SELECT trove_cat_id,parent,fullname FROM trove_cat '
        . 'WHERE trove_cat_id=' . db_ei($currentcat));
        $row = db_fetch_array($res);
        $return = $row["fullname"] . ($first ? "" : " :: ") . $return;
        $currentcat = $row["parent"];
        $first = 0;
    }
    return $return;
}

function trove_get_visibility_for_user($field, PFUser $user)
{
    if (ForgeConfig::areRestrictedUsersAllowed() && $user->isRestricted()) {
        return $field . ' = "' . db_es(Project::ACCESS_PUBLIC_UNRESTRICTED) . '"';
    } else {
        return $field . ' NOT IN ("' . db_es(Project::ACCESS_PRIVATE) . '", "' . db_es(Project::ACCESS_PRIVATE_WO_RESTRICTED) . '")';
    }
}
