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


$Language->loadLanguageMsg('svn/svn');

if (!$group_id) {
    exit_no_group(); // need a group_id !!!
}

svn_header(array ('title'=>$Language->getText('svn_intro','info')));

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
print $HTML->box1_top($Language->getText('svn_intro','history'));

echo svn_utils_format_svn_history($group_id);

// SVN Browsing Box
print '<HR><B>'.$Language->getText('svn_intro','browse_tree').'</B>
<P>'.$Language->getText('svn_intro','browse_comment').'
<UL>
<LI><A HREF="/svn/viewcvs.php/?roottype=svn&root='.$row_grp['unix_group_name'].'"><B>'.$Language->getText('svn_intro','browse_tree').'</B></A></LI>';

print $HTML->box1_bottom();

print '</TD></TR></TABLE>';

svn_footer(array());
?>
