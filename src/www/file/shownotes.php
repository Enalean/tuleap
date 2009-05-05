<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

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
if($request->valid(new Valid_UInt('release_id'))) {
    $release_id = $request->get('release_id');
} else {
    exit_error($GLOBALS['Language']->getText('file_shownotes','not_found_err'),$GLOBALS['Language']->getText('file_shownotes','release_not_found'));
}

$frsrf = new FRSReleaseFactory();
$release =& $frsrf->getFRSReleaseFromDb($release_id);


if (!$release || !$release->isActive() || !$release->userCanRead()) {
	exit_error($Language->getText('file_shownotes','not_found_err'),$Language->getText('file_shownotes','release_not_found'));
} else {

    $hp =& CodeX_HTMLPurifier::instance();
	$group_id = $release->getGroupID();

	file_utils_header(array('title'=>$Language->getText('file_shownotes','release_notes'),'group'=>$group_id));

	$HTML->box1_top($Language->getText('file_shownotes','notes'));

	echo '<h3>'.$Language->getText('file_shownotes','release_name').': <A HREF="showfiles.php?group_id='.$group_id.'">'.$hp->purify($release->getName()).'</A></H3>
		<P>';

/*
	Show preformatted or plain notes/changes
*/
	$purify_level = CODEX_PURIFIER_BASIC;
    if ($release->isPreformatted()) {
		echo '<PRE>'.PHP_EOL;
        $purify_level = CODEX_PURIFIER_BASIC_NOBR;
    }
    echo '<B>'.$Language->getText('file_shownotes','notes').':</B>'.PHP_EOL
         .$hp->purify($release->getNotes(), $purify_level, $group_id).
        '<HR NOSHADE SIZE=1>'.
        '<B>'.$Language->getText('file_shownotes','changes').':</B>'.PHP_EOL
        .$hp->purify($release->getChanges(), $purify_level, $group_id);
	if ($release->isPreformatted()) {
		echo '</PRE>';
    }
    

	$HTML->box1_bottom();

	file_utils_footer(array());

}

?>
