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

// Check if this tracker is valid (not deleted)
if (! $ath->isValid()) {
    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_add', 'invalid'));
}

// Create factories
$art_field_fact    = new ArtifactFieldFactory($ath);
$art_fieldset_fact = new ArtifactFieldSetFactory($ath);

// Printer version ?
$ro = false;
if (! $request->exist('pv')) {
    $pv = false;
    $ro = ! user_isloggedin();
} else {
    $pv = $request->get('pv');
    if ($pv) {
        $ro = true;
    }
}

$params = ['title' => $group->getPublicName() . ' ' . $ath->getName() . ' #' . $ah->getID() . ' - \'' . $ah->getSummary() . '\'',
    'pagename' => 'tracker',
    'atid' => $ath->getID(),
    'pv' => $pv,
];

$ath->header($params);


// artifact object (and field values) initialized in script above (index.php)
$ah->display($ro, $pv, UserManager::instance()->getCurrentUser()->getId());

echo '<script type="text/javascript">' . "\n";
$armh = new ArtifactRulesManagerHtml($ath);
$armh->displayRulesAsJavascript();
echo "Event.observe(window, 'load', function() {
        if ($('tracker_details')) {
            new com.xerox.codendi.FieldEditor('tracker_details', {
                edit:    '" . addslashes($Language->getText('tracker_fieldeditor', 'edit')) . "',
                preview: '" . addslashes($Language->getText('tracker_fieldeditor', 'preview')) . "',
                warning: '" . addslashes($Language->getText('tracker_fieldeditor', 'warning')) . "',
                group_id:" . (int) $ath->getGroupId() . "
            });
        }
});";

echo "new UserAutoCompleter('tracker_cc',
                          '" . util_get_dir_image_theme() . "',
                          true);\n";
echo "</script>";

// Display footer page
$ath->footer($params);
