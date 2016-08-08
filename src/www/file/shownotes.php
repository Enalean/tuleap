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
require_once('common/reference/CrossReferenceFactory.class.php');

// NTY Now only for registered users on Codendi
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
    $user            = UserManager::instance()->getCurrentUser();
    $additional_view = getAdditionalView($release, $user);

    $group_id = $release->getGroupID();
    file_utils_admin_header(array('title'=>$Language->getText('file_shownotes','release_notes'),'group'=>$group_id));

    if ($additional_view) {
        echo $additional_view;
    } else {
        $hp =& Codendi_HTMLPurifier::instance();

        $HTML->box1_top($Language->getText('file_shownotes','notes'));

        echo '<h3>'.$Language->getText('file_shownotes','release_name').': <A HREF="showfiles.php?group_id='.$group_id.'">'.$hp->purify($release->getName()).'</A></H3>
            <P>';

    /*
        Show preformatted or plain notes/changes
    */
        $purify_level = CODENDI_PURIFIER_BASIC;
        if ($release->isPreformatted()) {
            echo '<PRE>'.PHP_EOL;
            $purify_level = CODENDI_PURIFIER_BASIC_NOBR;
        }
        echo '<B>'.$Language->getText('file_shownotes','notes').':</B>'.PHP_EOL
             .$hp->purify($release->getNotes(), $purify_level, $group_id).
            '<HR NOSHADE SIZE=1>'.
            '<B>'.$Language->getText('file_shownotes','changes').':</B>'.PHP_EOL
            .$hp->purify($release->getChanges(), $purify_level, $group_id);
        if ($release->isPreformatted()) {
            echo '</PRE>';
        }

        $crossref_fact= new CrossReferenceFactory($release_id, ReferenceManager::REFERENCE_NATURE_RELEASE, $group_id);
        $crossref_fact->fetchDatas();
        if ($crossref_fact->getNbReferences() > 0) {
            echo '<hr noshade>';
            echo '<b> '.$Language->getText('cross_ref_fact_include','references').'</b>';
            $crossref_fact->DisplayCrossRefs();
        }
    }

	$HTML->box1_bottom();

	file_utils_footer(array());

}

function getAdditionalView(FRSRelease $release, PFUser $user)
{
    $view = '';
    $params = array(
        'release' => $release,
        'user'    => $user,
        'view'    => &$view
    );

    EventManager::instance()->processEvent('frs_release_view', $params);

    return $view;
}

?>
