<?php
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
//
//
//
//  Written for Codendi by Marie-Luise Schneider
// Check if this tracker is valid (not deleted)
if (! $ath->isValid()) {
    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_add', 'invalid'));
}

// Create factories
$art_field_fact = new ArtifactFieldFactory($ath);

// Printer version ?
$ro = false;
if (! $request->exist('pv')) {
    $pv = false;
    $ro = false;
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
echo '<div id="tracker_toolbar_clear"></div>';

// artifact object (and field values) initialized in script above (index.php)
$ah->displayCopy($ro, $pv);

$GLOBALS['Response']->includeFooterJavascriptFile('/scripts/trackerv3_artifact.js');

echo "<script type=\"text/javascript\">\n";
$armh = new ArtifactRulesManagerHtml($ath);
$armh->displayRulesAsJavascript();
echo "new UserAutoCompleter('tracker_cc',
                          '" . util_get_dir_image_theme() . "',
                          true);\n";
echo "</script>";

// Display footer page
$ath->footer($params);
