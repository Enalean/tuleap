<?php
// Copyright (c) Enalean, 2012-Present. All Rights Reserved.
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
//
//
//
//  Written for Codendi by Nicolas Guérin
//
// This script performs a redirection to the proper artifact page, given
// only the artifact id, and possibly the artifact name detected in text and a group_id.
// It is called from function util_make_links

  /********************************************************************
   * WARNING *
   *    Please note that this script has been replaced by src/www/goto
   *    We only keep it for compatibility with existing commit messages and legacy trackers
   ********************************************************************/


require_once('../svn/svn_data.php');

/**
 * Redirect function for generic trackers.
 * This function checks the artifact short name and project.
 * If the name is not the same as the tracker's short name, a warning is displayed.
 * If the artifact does not belong to the same project as the referring page, a warning is also displayed.
 */
function generic_redirect($location, $aid, $group_id, $art_group_id, $atid, $atn, $art_name)
{
    global $Language;
    $feed = '';
    if (($group_id) && ($group_id != $art_group_id)) {
        // The link is coming from another project, add a warning msg
        $group_name = util_get_group_name_from_id($art_group_id);
        $feed       = "&feedback=" . urlencode($Language->getText('tracker_gotoid', 'art_belongs_to', $group_name));
    }
    if (($atn) && (strtolower($atn) != strtolower($art_name))) {
        if ((strtolower($atn) != "art") && (strtolower($atn) != "artifact")) {
            $feed .= urlencode($Language->getText('tracker_gotoid', 'art_is_a', [$art_name, $atn]));
        }
    }

    $location .= "/tracker/?func=detail&aid=" . (int) $aid . "&group_id=" . (int) $art_group_id . "&atid=" . ((int) $atid) . $feed;
    header($location);
    exit;
}


// Start of main code

$location = 'Location: ';

// $atn is the "artifact type name" i.e. the tracker short name detected in the text
// Detected: 'xxx #nnn', transformed to  '$atn #$aid'
$atn = strtolower($request->get('atn'));

// If group_name given as argument then infer group_id first
$group_name = $request->get('group_name');
if ($group_name && ! $group_id) {
    $grp      = group_get_object_by_name($group_name);
    $group_id = $grp->getGroupId();
}

// Commit and patch are not ambiguous (not trackers)
$svn_loc = "/svn/?func=detailrevision&rev_id=" . (int) $aid . "&group_id=" . (int) $group_id;
if (($atn == 'rev') || ($atn == 'revision')) {
    $location .= $svn_loc;
    header($location);
    exit;
}
if ($atn == 'commit') {
    // when commit is used see if it revision exists in SVN else redirect to CVS
    $res  = svn_data_get_revision_detail($group_id, 0, $aid);
    $feed = '';
    if ($res && db_numrows($res) > 0) {
        $location .= $svn_loc . $feed;
        header($location);
        exit;
    }
}


// Should we remove this one?
if (! $group_id) {
    // group_id is necessary for legacy trackers -> link to generic tracker
    $art_group_id = $request->get('art_group_id');
    $art_name     = $request->get('art_name');
    if (! util_get_ids_from_aid($aid, $art_group_id, $atid, $art_name)) {
        exit_error($Language->getText('global', 'error'), $Language->getText('tracker_gotoid', 'invalid_art_nb', $aid));
    }
    generic_redirect($location, $aid, $art_group_id, $art_group_id, $atid, $atn, $art_name);
}

// not standard atn -> generic tracker.
if (! util_get_ids_from_aid($aid, $art_group_id, $atid, $art_name)) {
    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_gotoid', 'invalid_art_nb', $aid));
}
generic_redirect($location, $aid, $group_id, $art_group_id, $atid, $atn, $art_name);
