<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//  Written for CodeX by Nicolas Guérin
//
// This script performs a redirection to the proper artifact page, given 
// only the artifact id, and possibly the artifact name detected in text and a group_id.
// It is called from function util_make_links

require('../svn/svn_data.php');
$LANG->loadLanguageMsg('tracker/tracker');

// Redirect function for legacy trackers (bug, task and SR)
function legacy_redirect($location,$aid, $group_id, $atn) {
    if ($atn == 'bug') {
        $location .= "/bugs/?func=detailbug&bug_id=$aid&group_id=$group_id";
        header($location);
        exit;
    }
    if ($atn == 'task') {
        $location .= "/pm/task.php?func=detailtask&project_task_id=$aid&group_id=$group_id";
        header($location);
        exit;
    }
    if ($atn == 'sr') {
        $location .= "/support/index.php?func=detailsupport&support_id=$aid&group_id=$group_id";
        header($location);
        exit;
    }
    if ($atn == 'patch') {
      $location .= "/patch/?func=detailpatch&patch_id=$aid&group_id=$group_id";
      header($location);
      exit;
    }
    
}

/**
 * Redirect function for generic trackers.
 * This function checks the artifact short name and project.
 * If the name is not the same as the tracker's short name, a warning is displayed.
 * If the artifact does not belong to the same project as the referring page, a warning is also displayed.
 */
function generic_redirect($location,$aid,$group_id,$art_group_id,$atid,$atn,$art_name) {
  global $LANG;

    if (($group_id)&&($group_id != $art_group_id)) {
        // The link is coming from another project, add a warning msg
        $group_name=util_get_group_name_from_id($art_group_id);
        $feed="&feedback=".$LANG->getText('tracker_gotoid','art_belongs_to',$group_name);
    }
    if (($atn)&&(strtolower($atn) != strtolower($art_name))) {
        if ((strtolower($atn)!="art")&&(strtolower($atn)!="artifact")) {
            $feed.=$LANG->getText('tracker_gotoid','art_is_a',array($art_name,$atn));
        }
    }

    $location .= "/tracker/?func=detail&aid=".$aid."&group_id=".$art_group_id."&atid=".$atid.$feed;
    header($location);
    exit;

}


// Start of main code

$location = "Location: ".get_server_url();

// $atn is the "artifact type name" i.e. the tracker short name detected in the text
// Detected: 'xxx #nnn', transformed to  '$atn #$aid'
$atn=strtolower($atn);

// If group_name given as argument then infer group_id first
if ($group_name && !$group_id) {
    $grp = group_get_object_by_name($group_name);
    $group_id = $grp->getGroupId();
}

// Commit and patch are not ambiguous (not trackers)
$svn_loc = "/svn/?func=detailrevision&commit_id=$aid&group_id=$group_id";
$cvs_loc = "/cvs/?func=detailcommit&commit_id=$aid&group_id=$group_id";
if (($atn == 'rev') || ($atn == 'revision')) {
    $location .= $svn_loc;
    header($location);
    exit;
}
if ($atn == 'commit') {
    // when commit is used see if it revision exists in SVN else redirect to CVS
    $res = svn_data_get_revision_detail($group_id, 0, $aid);
    if ($res && db_numrows($res) > 0) {
	$location .= $svn_loc.$feed;
    } else {
        // Check that the commit belongs to the same project
        $commit_group_id=util_get_group_from_commit_id($aid);
        if (($commit_group_id)&&($group_id != $commit_group_id)) {
            // The link is coming from another project, add a warning msg
            $group_name=util_get_group_name_from_id($commit_group_id);
            $feed="&feedback".$LANG->getText('tracker_gotoid','commit_belongs_to',$group_name);
        }
	$location .= $cvs_loc.$feed;
    }
    header($location);
    exit;
}


if ((!$sys_activate_tracker)) {
    // If generic trackers are not available, then use only legacy!
    legacy_redirect($location,$aid,$group_id,$atn);
    exit_error($LANG->getText('global','error'),$LANG->getText('tracker_gotoid', 'invalid_tracker_vb',$atn));
}

// Should we remove this one?
if (!$group_id) {
    // group_id is necessary for legacy trackers -> link to generic tracker
    if (!util_get_ids_from_aid($aid,$art_group_id,$atid,$art_name)) {
        exit_error($LANG->getText('global','error'),$LANG->getText('tracker_gotoid', 'invalid_art_nb', $aid));
    }
    generic_redirect($location,$aid,$art_group_id,$art_group_id,$atid,$atn,$art_name);
}


// Now check ambiguous cases...
if (($atn == 'bug')||($atn == 'task')||($atn == 'sr')||($atn == 'patch')) {
    // Ambiguous: legacy or generic tracker?
    // Get artifact group_id and tracker id (atid)
    if (!util_get_ids_from_aid($aid,$art_group_id,$atid,$art_name)) {
        // The artifact does not exist -> legacy
        legacy_redirect($location,$aid,$group_id,$atn);
        exit_error($LANG->getText('global','error'),$LANG->getText('tracker_gotoid', 'invalid_art_nb', $aid));
    }
    // Are the legacy trackers activated for this project? 
    $grp=project_get_object($group_id);
    if ((($atn == 'bug')&&(!$grp->activateOldBug()))
        ||(($atn == 'sr')&&(!$grp->activateOldSR()))
        ||(($atn == 'task')&&(!$grp->activateOldTask()))
||(($atn == 'patch')&&(!$grp->activateOldPatch()))) {
        // Legacy tracker is not activated -> this is a generic one
        generic_redirect($location,$aid,$group_id,$art_group_id,$atid,$atn,$art_name);
    }

    // Does the legacy bug/sr/task id exists and does it belong to this project?
    $legacy_group_id=util_get_group_from_legacy_id($atn,$aid);
    if ((!$legacy_group_id)||($legacy_group_id!=$group_id)) {
        // the legacy artifact does not exist or does not belong to the current project
        // -> this is a generic artifact
        generic_redirect($location,$aid,$group_id,$art_group_id,$atid,$atn,$art_name);
    }

    // OK, so both the legacy and the generic artifact exist with this id.
    // Now check the artifact name. if it is not 'sr', 'bug' or 'task', then
    // use redirect to legacy. 
    // If the artifact belongs to another project, then also redirect to legacy.
    // Otherwise, prefer the generic artifact.
    // This is the only place where there is still an arbitrary decision made...
    if (($atn != strtolower($art_name))||($group_id!=$art_group_id)) {
        // Let's choose the legacy trackers
        legacy_redirect($location,$aid,$group_id,$atn);
        exit_error($LANG->getText('global', 'error'),$LANG->getText('tracker_gotoid', 'invalid_tracker'));
    } else {
        // If the artifact belongs to the current project, let's choose the generic trackers
        generic_redirect($location,$aid,$group_id,$art_group_id,$atid,$atn,$art_name);
    }   

} else {
    // not standard atn -> generic tracker.
    if (!util_get_ids_from_aid($aid,$art_group_id,$atid,$art_name)) {
        exit_error($LANG->getText('global','error'),$LANG->getText('tracker_gotoid', 'invalid_art_nb', $aid));
    }
    generic_redirect($location,$aid,$group_id,$art_group_id,$atid,$atn,$art_name);
}



?>
