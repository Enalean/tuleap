<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
//	Originally written by Laurent Julliard 2001- 2003 CodeX Team, Xerox
//

if (!$group_id) {
    exit_no_group(); // need a group_id !!!
}

$LANG->loadLanguageMsg('cvs/cvs');

commits_header(array ('title'=>$LANG->getText('cvs_intro', 'title')));

// Table for summary info
print '<TABLE width="100%"><TR valign="top"><TD width="65%">'."\n";

// Get group properties
$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");
$row_grp = db_fetch_array($res_grp);

// Show CVS access information
if ($row_grp['cvs_preamble']!='') {
    echo util_unconvert_htmlspecialchars($row_grp['cvs_preamble']);
} else {
    include(util_get_content('cvs/intro'));
}

// Summary info
print '</TD><TD width="25%">';
print $HTML->box1_top($LANG->getText('cvs_intro', 'repo_history'));

// Is there anything in the cvs history table ?
$res_cvshist = db_query("SELECT * FROM group_cvs_history WHERE group_id='$group_id'");
if (db_numrows($res_cvshist) < 1) {
    print '<P>'.$LANG->getText('cvs_intro', 'no_history');
} else {

    print '<P><b>'.$LANG->getText('cvs_intro', 'nb_commits').'</b><BR>&nbsp;';

    while ($row_cvshist = db_fetch_array($res_cvshist)) {
        print '<BR>'.$row_cvshist['user_name'].' ('.$row_cvshist['cvs_commits_wk'].'/'
	    .$row_cvshist['cvs_commits'].') ('.$row_cvshist['cvs_adds_wk'].'/'
	    .$row_cvshist['cvs_adds'].')';
    }

}

// CVS Browsing Box
$uri = session_make_url('/cvs/viewcvs.php/?root='.$row_grp['unix_group_name'].'&roottype=cvs');
print '<HR><B>'.$LANG->getText('cvs_intro', 'browse_title').'</B>
<P>'.$LANG->getText('cvs_intro', 'browse_msg').'
<UL>
<LI><A href="'.$uri.'"><B>Browse CVS Tree</B></A></LI>';

print $HTML->box1_bottom();

print '</TD></TR></TABLE>';

commits_footer(array());
?>
