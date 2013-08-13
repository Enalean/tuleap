<?php

//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
// 
//
//
//  Written for Codendi by Marie-Luise Schneider
//

//require_once('common/include/Error.class.php');
//require_once('common/tracker/ArtifactField.class.php');
//require_once('include/ArtifactFieldHtml.class.php');
//require_once('common/tracker/ArtifactFieldFactory.class.php');

require_once('include/ArtifactRulesManagerHtml.class.php');

// Check if this tracker is valid (not deleted)
if ( !$ath->isValid() ) {
	exit_error($Language->getText('global','error'),$Language->getText('tracker_add','invalid'));
}

// Create factories
$art_field_fact = new ArtifactFieldFactory($ath);

// Printer version ?
if ( !$request->exist('pv')) {
	$pv = false;
	$ro = false;
} else {
    $pv = $request->get('pv');
	if ( $pv ) $ro = true;
}

$params=array('title'=>$group->getPublicName().' '.$ath->getName().' #'.$ah->getID(). ' - \'' . $ah->getSummary().'\'',
              'pagename'=>'tracker',
              'atid'=>$ath->getID(),
              'sectionvals'=>array($group->getPublicName()),
              'pv'=>$pv,
              'help' => 'tracker-v3.html#artifact-update');

$ath->header($params);
echo '<div id="tracker_toolbar_clear"></div>';

// artifact object (and field values) initialized in script above (index.php)
$ah->displayCopy($ro,$pv);

$GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/tiny_mce/tiny_mce.js');

echo "<script type=\"text/javascript\">\n";
$armh = new ArtifactRulesManagerHtml($ath);
$armh->displayRulesAsJavascript();
echo "new UserAutoCompleter('tracker_cc',
                          '".util_get_dir_image_theme()."',
                          true);\n";
echo "document.observe(\"dom:loaded\", function() {
    new Codendi_RTE_Light_Tracker_FollowUp(\"follow_up_comment\");
});";
echo "</script>";

// Display footer page
$ath->footer($params);

?>
