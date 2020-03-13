<?php
// Tuleap
// Copyright (c) Enalean, 2016-Present. All Rights Reserved.
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
//
//
//    Originally written by Laurent Julliard 2001- 2003 Codendi Team, Xerox
require_once __DIR__ . '/commit_utils.php';

$request  = HTTPRequest::instance();
$group_id = $request->get('group_id');

if (!$group_id) {
    exit_no_group(); // need a group_id !!!
}


commits_header(array(
    'title' => $GLOBALS['Language']->getText('cvs_intro', 'title'),
    'group' => $group_id
));

// Table for summary info
print '<TABLE width="100%"><TR valign="top"><TD width="65%">' . "\n";

// Get group properties
$res_grp = db_query("SELECT * FROM groups WHERE group_id=" . db_ei($group_id));
$row_grp = db_fetch_array($res_grp);

// Show CVS access information
if ($row_grp['cvs_preamble'] != '') {
    echo util_unconvert_htmlspecialchars($row_grp['cvs_preamble']);
} else {
    include($GLOBALS['Language']->getContent('cvs/intro'));
}

// Summary info
print '</TD><TD width="25%">';
print $HTML->box1_top($GLOBALS['Language']->getText('cvs_intro', 'repo_history'));
echo format_cvs_history($group_id);


// CVS Browsing Box
$uri = session_make_url('/cvs/viewvc.php/?root=' . $row_grp['unix_group_name'] . '&roottype=cvs');
print '<HR><B>' . $GLOBALS['Language']->getText('cvs_intro', 'browse_title') . '</B>
<P>' . $GLOBALS['Language']->getText('cvs_intro', 'browse_msg') . '
<UL>
<LI><A href="' . $uri . '"><B>' . $GLOBALS['Language']->getText('cvs_commit_utils', 'menu_browse') . '</B></A></LI>';

print $HTML->box1_bottom();

print '</TD></TR></TABLE>';

commits_footer(array());
