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

svn_header(array ('title'=>'Subversion Information'));

// Table for summary info
print '<TABLE width="100%"><TR valign="top"><TD width="65%">'."\n";

// Get group properties
$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");
$row_grp = db_fetch_array($res_grp);

// Show CVS access information
if ($row_grp['svn_preamble'] != '') {
    echo util_unconvert_htmlspecialchars($row_grp['svn_preamble']);
} else {
    include(util_get_content('svn/intro'));
}

// Summary info
print '</TD><TD width="25%">';
print $HTML->box1_top("Repository History");

echo svn_utils_format_svn_history($group_id);

// SVN Browsing Box
$uri = session_make_url('/cgi-bin/viewcvs.cgi/?root='.$row_grp['unix_group_name'].'&roottype=svn');
print '<HR><B>Browse the Subversion Tree</B>
<P>Browsing the Subversion tree gives you a great view into the current status
of this project\'s code. You may also view the complete histories of any
file in the repository.
<UL>
<LI><A href="'.$uri.'"><B>Browse Subversion Tree</B></A></LI>';

print $HTML->box1_bottom();

print '</TD></TR></TABLE>';

svn_footer(array());
?>
