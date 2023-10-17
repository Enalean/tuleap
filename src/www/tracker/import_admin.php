<?php
// Copyright (c) Enalean SAS, 2017 - Present. All rights reserved
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
//
//
//
//  Written for Codendi by Marie-Luise Schneider
require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../project/admin/project_admin_utils.php';

// Inherited from old .htaccess (needed for reports, linked artifact view, etc)
ini_set('max_execution_time', 1800);

$group_id = $request->getValidated('group_id', 'GroupId');
$mode     = $request->get('mode');
if ($group_id && $mode == "admin") {
    $hp = Codendi_HTMLPurifier::instance();
  //   the welcome screen when entering the import facility from admin page

    session_require(['group' => $group_id, 'admin_flags' => 'A']);

  //  get the Group object
    $pm    = ProjectManager::instance();
    $group = $pm->getProject($group_id);
    if (! $group || ! is_object($group) || $group->isError()) {
        exit_no_group();
    }
    $atf = new ArtifactTypeFactory($group);
    if (! $group || ! is_object($group) || $group->isError()) {
        exit_error($Language->getText('global', 'error'), $Language->getText('tracker_import_admin', 'not_get_atf'));
    }


    $pg_title = $Language->getText('tracker_import_admin', 'art_import');


    project_admin_header(
        $pg_title,
        \Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder::DATA_ENTRY_SHORTNAME
    );

    $pm      = ProjectManager::instance();
    $project = $pm->getProject($group_id);
    if (! $project->usesTracker()) {
        echo '<P> ' . $Language->getText('tracker_import_admin', 'disabled');
        project_admin_footer([]);
    } else {
    // Display the welcome screen
        echo $Language->getText('tracker_import_admin', 'welcome');

    // Show all the fields currently available in the system
        echo '<p><TABLE WIDTH="100%" BORDER="0" CELLSPACING="1" CELLPADDING="2">';
        echo '
  <tr class="boxtable">
    <td class="boxtitle">&nbsp;</td>
    <td class="boxtitle">
      <div align="center"><b>' . $Language->getText('tracker_import_admin', 'art_data_import') . '</b></div>
    </td>
    <td class="boxtitle">
      <div align="center"><b>' . $Language->getText('tracker_import_admin', 'import_format') . '</b></div>
    </td>
 </tr>';

    // Get the artfact type list
        $at_arr = $atf->getArtifactTypes();

        if ($at_arr && count($at_arr) >= 1) {
            for ($j = 0; $j < count($at_arr); $j++) {
                echo '
		  <tr class="' . util_get_alt_row_color($j) . '">
		    <td><b>' . $Language->getText('tracker_import_admin', 'tracker') . ': ' . $hp->purify(SimpleSanitizer::unsanitize($at_arr[$j]->getName()), CODENDI_PURIFIER_CONVERT_HTML) . '</b></td>
		    <td align="center">
                      <a href="/tracker/index.php?group_id=' . (int) $group_id . '&atid=' . (int) ($at_arr[$j]->getID()) . '&user_id=' . (int) UserManager::instance()->getCurrentUser()->getId() . '&func=import">' . $Language->getText('tracker_import_admin', 'import') . '</a>
		    </td>
		    <td align="center">
		      <a href="/tracker/index.php?group_id=' . (int) $group_id . '&atid=' . (int) ($at_arr[$j]->getID()) . '&user_id=' . (int) UserManager::instance()->getCurrentUser()->getId() . '&mode=showformat&func=import">' . $Language->getText('tracker_import_admin', 'show_format') . '</a>
		    </td>
		  </tr>';
            }
        }

        echo '</TABLE>';
        project_admin_footer([]);
    }
} else {
    exit_missing_param();
}
