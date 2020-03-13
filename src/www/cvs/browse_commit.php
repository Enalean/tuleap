<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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


require_once __DIR__ . '/commit_utils.php';

$request  = HTTPRequest::instance();
$group_id = $request->get('group_id');

if (!$group_id) {
    exit_no_group();
}


commits_header(array(
    'title' => $GLOBALS['Language']->getText('cvs_browse_commit', 'title'),
    'help'  => 'cvs.html#querying-cvs',
    'group' => $group_id
));

$offset = (int) $request->get('offset');
if ($offset < 0) {
    $offset = 0;
}

$chunksz = (int) $request->get('chunksz');
if ($chunksz < 1) {
    $chunksz = 15;
}

$msort = (int) $request->get('msort');
if (($msort !== 0) && ($msort !== 1)) {
    $msort = 0;
}

$morder = $request->get('morder');
if (user_isloggedin() && $morder === false) {
    $morder = user_get_preference('commit_browse_order' . $group_id);
}

$order = $request->get('order');
if ($order !== false) {
    if ($order != '') {
        // Add the criteria to the list of existing ones
        $morder = commit_add_sort_criteria($morder, $order, $msort);
    } else {
        // reset list of sort criteria
        $morder = '';
    }
}

$order_by = '';
if ($morder !== false) {
    if (user_isloggedin()) {
        if ($morder != user_get_preference('commit_browse_order' . $group_id)) {
            user_set_preference('commit_browse_order' . $group_id, $morder);
        }
    }

    if ($morder !== '') {
        $order_by = ' ORDER BY ' . commit_criteria_list_to_query($morder);
    }
}

$pm = ProjectManager::instance();
$project = $pm->getProject($group_id);
$projectname = $project->getUnixName(false);

// Memorize order by field as a user preference if explicitly specified.
// Automatically discard invalid field names.
if ($order !== false) {
    if ($order == 'id' || $order == 'description' || $order == 'date' || $order == 'submitted_by') {
        if (user_isloggedin() &&
        ($order != user_get_preference('commits_browse_order'))) {
            user_set_preference('commits_browse_order', $order);
        }
    } else {
        $order = false;
    }
} else {
    if (user_isloggedin()) {
        $order = user_get_preference('commits_browse_order');
    }
}

$set = false;
if ($request->exist('set')) {
    $set = $request->get('set');
}

$_tag       = 100;
$_branch    = 100;
$_commit_id = '';
$_commiter  = 100;
$_srch      = '';
$pv         = $request->get('pv');

if (! $set) {
    /*
        if no set is passed in, see if a preference was set
        if no preference or not logged in, use my set
    */
    if (user_isloggedin()) {
        $custom_pref = user_get_preference('commits_browcust' . $group_id);
        if ($custom_pref) {
            $pref_arr = explode('|', $custom_pref);
            $_commit_id = $pref_arr[0];
            $_commiter = $pref_arr[1];
            $_tag = $pref_arr[2];
            $_branch = $pref_arr[3];
            $_srch = $pref_arr[4];
            $chunksz = $pref_arr[5];
            $set = 'custom';
        } else {
            $set = 'custom';
        }
    } else {
        $set = 'custom';
    }
}

if ($set == 'my') {
    /*
        My commits - backwards compat can be removed 9/10
    */
    $_commiter = user_getname();
} elseif ($set == 'custom') {
    /*
        if this custom set is different than the stored one, reset preference
    */
    $pref_ = $_commit_id . '|' . $_commiter . '|' . $_tag . '|' . $_branch . '|' . $_srch . '|' . $chunksz;
    if ($pref_ != user_get_preference('commits_browcust' . $group_id)) {
     //echo 'setting pref';
        user_set_preference('commits_browcust' . $group_id, $pref_);
    }
} elseif ($set == 'any') {
    /*
        Closed commits - backwards compat can be removed 9/10
    */
    // Do nothing > Done with default values;
}

/*
    Display commits based on the form post - by user or status or both
*/
$_tag      = $request->exist('_tag') ? $request->get('_tag') : $_tag;
$_branch   = $request->exist('_branch') ? $request->get('_branch') : $_branch;
$_commit_id = $request->exist('_commit_id') ? $request->get('_commit_id') : $_commit_id;
$_commiter = $request->exist('_commiter') ? $request->get('_commiter') : $_commiter;
$_srch     = $request->exist('_srch') ? $request->get('_srch') : $_srch;

list($result, $totalrows) = cvs_get_revisions($project, $offset, $chunksz, $_tag, $_branch, $_commit_id, $_commiter, $_srch, $order_by, $pv);

/*
    creating a custom technician box which includes "any"
*/

$tech_box = commits_technician_box($projectname, '_commiter', $_commiter, 'Any');



/*
    Show the new pop-up boxes to select assigned to and/or status
*/
echo '<H3>' . $GLOBALS['Language']->getText('cvs_browse_commit', 'browse_by') . ':</H3>';
echo '<FORM class="form-inline" name="commit_form" ACTION="?" METHOD="GET">
        <TABLE WIDTH="10%" BORDER="0">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . $group_id . '">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="browse">
	<INPUT TYPE="HIDDEN" NAME="set" VALUE="custom">
        <TR align="center">
                      <TD><b>' . $GLOBALS['Language']->getText('cvs_browse_commit', 'id') . '</b></TD>
                      <TD><b>' . $GLOBALS['Language']->getText('cvs_browse_commit', 'branch') . '</b></TD>
                      <TD><b>' . $GLOBALS['Language']->getText('cvs_browse_commit', 'who') . '</b></TD>
                      <TD><b>' . $GLOBALS['Language']->getText('cvs_browse_commit', 'keyword') . '</b></TD>' .
        '</TR>' .
        '<TR><TD><INPUT TYPE="TEXT" CLASS="input-mini" SIZE=5 NAME=_commit_id VALUE=' . $_commit_id . '></TD>' .
        '<TD><FONT SIZE="-1">' . commits_branches_box($group_id, '_branch', $_branch, 'Any') . '</TD>' .
        '<TD><FONT SIZE="-1">' . $tech_box .
        '</TD><TD><FONT SIZE="-1">' . '<INPUT type=text size=35 name=_srch value=' . $_srch .
        '></TD>' .
       '</TR></TABLE>' .

'<br><FONT SIZE="-1"><INPUT TYPE="SUBMIT" CLASS="btn" NAME="SUBMIT" VALUE="' . $GLOBALS['Language']->getText('global', 'btn_browse') . '">' .
' <input CLASS="input-mini" TYPE="text" name="chunksz" size="3" MAXLENGTH="5" ' .
'VALUE="' . $chunksz . '">' . $GLOBALS['Language']->getText('cvs_browse_commit', 'nb_at_once') . '.' .
'</FORM>';


if ($result && db_numrows($result) > 0) {
    //create a new $set string to be used for next/prev button
    if ($set == 'custom') {
        $set .= '&_branch=' . $_branch . '&_commiter=' . $_commiter . '&_tag=' . $_tag . '&_srch=' . $_srch . '&chunksz=' . $chunksz;
    } elseif ($set == 'any') {
        $set .= '&_branch=100&_commiter=100&_tag=100&chunksz=' . $chunksz;
    }

    show_commitslist($group_id, $result, $offset, $totalrows, $set, $_commiter, $_tag, $_branch, $_srch, $chunksz, $morder, $msort);
} else {
    echo '
	       <H3>' . $GLOBALS['Language']->getText('cvs_browse_commit', 'no_commit') . '</H3>';
}

commits_footer(array());
