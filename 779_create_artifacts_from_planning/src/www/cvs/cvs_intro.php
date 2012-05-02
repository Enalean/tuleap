<?php
//
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
// 
//
//	Originally written by Laurent Julliard 2001- 2003 Codendi Team, Xerox
//

if (!$group_id) {
    exit_no_group(); // need a group_id !!!
}


commits_header(array ('title'=>$Language->getText('cvs_intro', 'title')));

// Table for summary info
print '<TABLE width="100%"><TR valign="top"><TD width="65%">'."\n";

// Get group properties
$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");
$row_grp = db_fetch_array($res_grp);

// Show CVS access information
if ($row_grp['cvs_preamble']!='') {
    echo util_unconvert_htmlspecialchars($row_grp['cvs_preamble']);
} else {
    include($Language->getContent('cvs/intro'));
}

// Summary info
print '</TD><TD width="25%">';
print $HTML->box1_top($Language->getText('cvs_intro', 'repo_history'));
echo format_cvs_history($group_id);


// CVS Browsing Box
$uri = session_make_url('/cvs/viewvc.php/?root='.$row_grp['unix_group_name'].'&roottype=cvs');
print '<HR><B>'.$Language->getText('cvs_intro', 'browse_title').'</B>
<P>'.$Language->getText('cvs_intro', 'browse_msg').'
<UL>
<LI><A href="'.$uri.'"><B>'.$Language->getText('cvs_commit_utils', 'menu_browse').'</B></A></LI>';

print $HTML->box1_bottom();

print '</TD></TR></TABLE>';

commits_footer(array());
?>
