<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//

require_once('pre.php');

$hp = Codendi_HTMLPurifier::instance();
session_require(array('group'=>'1','admin_flags'=>'A'));

$search = $request->getValidated('search', 'string', '');
if ($search == '') {
    $GLOBALS['Response']->addFeedback('error', $Language->getText('admin_search','error_nowholedb'));
    $GLOBALS['Response']->redirect('/admin');
}

if ($request->existAndNonEmpty('usersearch')) {
    $GLOBALS['Response']->redirect('/admin/userlist.php?user_name_search='.urlencode($search));
}

if ($request->existAndNonEmpty('groupsearch')) {
    $GLOBALS['Response']->redirect('/admin/grouplist.php?group_name_search='.urlencode($search));
}

$HTML->header(array('title'=>$Language->getText('admin_search','title')));

echo "<p>Legacy page, please search in group or user directly</p>";
$GLOBALS['Response']->redirect('/admin');

$HTML->footer(array());
?>
