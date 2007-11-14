<?php
/**
  *
  * SourceForge Generic Tracker facility
  *
  * SourceForge: Breaking Down the Barriers to Open Source Development
  * Copyright 1999-2001 (c) VA Linux Systems
  * http://sourceforge.net
  *
  *
  */

//require_once('common/include/Error.class.php');
//require_once('common/tracker/ArtifactField.class.php');
//require_once('include/ArtifactFieldHtml.class.php');
//require_once('common/tracker/ArtifactFieldFactory.class.php');

$Language->loadLanguageMsg('tracker/tracker');
require_once('include/ArtifactRulesManagerHtml.class.php');

// Check if this tracker is valid (not deleted)
if ( !$ath->isValid() ) {
	exit_error($Language->getText('global','error'),$Language->getText('tracker_add', 'invalid'));
}

// Create factories
$art_field_fact = new ArtifactFieldFactory($ath);
$art_fieldset_fact = new ArtifactFieldSetFactory($ath);

// Printer version ?
if ( !isset($pv) ) {
	$pv = false;
	$ro = !user_isloggedin();
} else {
	if ( $pv ) $ro = true;
}

$GLOBALS['HTML']->addFeed(
    $group->getPublicName().' '.$ath->getName() .' #'. $ah->getId() .' - '. html_entity_decode($ah->getValue('summary'), ENT_QUOTES) .' - '. $Language->getText('tracker_include_artifact','follow_ups'), 
    '/tracker/?func=rss&aid='. $ah->getId() .'&atid='. $ath->getID() .'&group_id='. $group->getGroupId()
);
$params=array('title'=>$group->getPublicName().' '.$ath->getName().' #'.$ah->getID(). ' - \'' . $ah->getSummary().'\'',
              'pagename'=>'tracker',
              'atid'=>$ath->getID(),
              'sectionvals'=>array($group->getPublicName()),
              'pv'=>$pv,
              'help' => 'ArtifactSubmission.html' );

$ath->header($params);


// artifact object (and field values) initialized in script above (index.php)
$ah->display($ro,$pv,user_getid());

echo '<script type="text/javascript">'. "\n";
$armh =& new ArtifactRulesManagerHtml($ath);
$armh->displayRulesAsJavascript();
echo "Event.observe(window, 'load', initFieldDependencies, true);\n";
echo "Event.observe(window, 'load', function() {
        if ($('tracker_details')) {
            new com.xerox.codex.FieldEditor('tracker_details', {
                edit:    '". addslashes($Language->getText('tracker_fieldeditor','edit')) ."',
                preview: '". addslashes($Language->getText('tracker_fieldeditor','preview')) ."',
                warning: '". addslashes($Language->getText('tracker_fieldeditor','warning')) ."',
                group_id:". $ath->getGroupId(). "
            });
        }
});";
echo "</script>";

// Display footer page
$ath->footer($params);

?>
