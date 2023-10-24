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

if ($request->exist('advsrch')) {
    $advsrch = $request->get('advsrch');
} else {
    $advsrch = 0;
}

$params = ['title' => $group->getPublicName() . ' ' . $ath->getName() . ' ' . $Language->getText('tracker_index', 'mass_change'),
    'pagename' => 'tracker',
    'atid' => $ath->getID(),
    'pv' => $pv,
];

$ath->header($params);
echo '<div id="tracker_toolbar_clear"></div>';

$submit = $request->get('submit_btn');
if (strstr($submit, $Language->getText('tracker_masschange_detail', 'selected_items'))) {
    $mass_change_ids = $request->get('mass_change_ids');
    if (! $mass_change_ids) {
        $feedback = $Language->getText('tracker_masschange_detail', 'no_items_selected');
    } else {
        $ath->displayMassChange($ro, $mass_change_ids);
    }
} else {
  // If still not defined then force it to system 'Default' report
    $report_id = $request->get('report_id');
    if (! $report_id) {
        $report_id = 100;
    }
  // Create factories
    $report_fact = new ArtifactReportFactory();
  // Create the HTML report object
    $art_report_html = $report_fact->getArtifactReportHtml($report_id, $atid);
    $query           = $art_field_fact->extractFieldList();
    $ath->displayMassChange($ro, null, $query, $art_report_html, $advsrch);
}

$GLOBALS['Response']->includeFooterJavascriptFile('/scripts/trackerv3_artifact.js');

echo '<script type="text/javascript">' . "\n";
$armh = new ArtifactRulesManagerHtml($ath);
$armh->displayRulesAsJavascript();

echo "new UserAutoCompleter('tracker_cc',
                          '" . util_get_dir_image_theme() . "',
                          true);\n";
echo "</script>\n";

// Display footer page
$ath->footer($params);
