<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require_once('www/file/file_utils.php');
require_once('common/frs/FRSReleaseFactory.class.php');
$Language->loadLanguageMsg('file/file');

// NTY Now only for registered users on CodeX
if (!user_isloggedin()) {
    /*
    Not logged in
    */
    exit_not_logged_in();
}
$frsrf = new FRSReleaseFactory();
$result = $frsrf->getFRSReleasesInfoByReleaseIdFromDb($release_id);


if (!$result || count($result) < 1) {
	exit_error($Language->getText('file_shownotes','not_found_err'),$Language->getText('file_shownotes','release_not_found'));
} else {

	$group_id=$result['group_id'];

	file_utils_header(array('title'=>$Language->getText('file_shownotes','release_notes'),'group'=>$group_id));

	$HTML->box1_top($Language->getText('file_shownotes','notes'));

	echo '<h3>'.$Language->getText('file_shownotes','release_name').': <A HREF="showfiles.php?group_id='.$result['group_id'].'">'.$result['name'].'</A></H3>
		<P>';

/*
	Show preformatted or plain notes/changes
*/
	if ($result['preformatted']) {
		echo '<PRE><B>'.$Language->getText('file_shownotes','notes').':</B>
'.$result['notes'].'

<HR NOSHADE>
<B>'.$Language->getText('file_shownotes','changes').':</B>
'.$result['changes'].'</PRE>';

	} else {
		echo '<B>'.$Language->getText('file_shownotes','notes').':</B>
'.$result['notes'].'

<HR NOSHADE>
<B>'.$Language->getText('file_shownotes','changes').':</B>
'.$result['changes'];

	}

	$HTML->box1_bottom();

	file_utils_footer(array());

}

?>
