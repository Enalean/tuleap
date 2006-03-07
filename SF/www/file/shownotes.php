<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require_once('www/file/file_utils.php');
$Language->loadLanguageMsg('file/file');

// NTY Now only for registered users on CodeX
if (!user_isloggedin()) {
    /*
    Not logged in
    */
    exit_not_logged_in();
}

$result=db_query("SELECT frs_release.notes,frs_release.changes,frs_release.preformatted,frs_release.name,frs_package.group_id ".
		"FROM frs_release,frs_package ".
		"WHERE frs_release.package_id=frs_package.package_id AND frs_release.release_id='$release_id'");

if (!$result || db_numrows($result) < 1) {
	echo db_error();
	exit_error($Language->getText('file_shownotes','not_found_err'),$Language->getText('file_shownotes','release_not_found'));
} else {

	$group_id=db_result($result,0,'group_id');

	file_utils_header(array('title'=>$Language->getText('file_shownotes','release_notes'),'group'=>$group_id));

	$HTML->box1_top($Language->getText('file_shownotes','notes'));

	echo '<h3>'.$Language->getText('file_shownotes','release_name').': <A HREF="showfiles.php?group_id='.db_result($result,0,'group_id').'">'.db_result($result,0,'name').'</A></H3>
		<P>';

/*
	Show preformatted or plain notes/changes
*/
	if (db_result($result,0,'preformatted')) {
		echo '<PRE><B>'.$Language->getText('file_shownotes','notes').':</B>
'.db_result($result,0,'notes').'

<HR NOSHADE>
<B>'.$Language->getText('file_shownotes','changes').':</B>
'.db_result($result,0,'changes').'</PRE>';

	} else {
		echo '<B>'.$Language->getText('file_shownotes','notes').':</B>
'.db_result($result,0,'notes').'

<HR NOSHADE>
<B>'.$Language->getText('file_shownotes','changes').':</B>
'.db_result($result,0,'changes');

	}

	$HTML->box1_bottom();

	file_utils_footer(array());

}

?>
