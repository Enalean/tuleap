<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Tracker\ArtifactPendingDeletionPresenter;

require_once __DIR__ . '/../../include/pre.php';

// Inherited from old .htaccess (needed for reports, linked artifact view, etc)
ini_set('max_execution_time', 1800);

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

$include_assets = new \Tuleap\Layout\IncludeAssets(__DIR__ . '/../../../scripts/site-admin/frontend-assets', '/assets/core/site-admin');
$GLOBALS['HTML']->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($include_assets, 'site-admin-trackers-pending-removal.js'));

$pm   = ProjectManager::instance();
$func = $request->get('func');
switch ($func) {
    case 'restore':
        if ($group = $pm->getProject($request->getValidated('group_id', 'GroupId'))) {
            $atid =  $request->get('atid');
            $ath  = new ArtifactType($group, $atid);
            if (! $ath->restore()) {
                $feedback = $GLOBALS['Language']->getText('tracker_admin_restore', 'restore_failed');
            } else {
                $feedback = $GLOBALS['Language']->getText('tracker_admin_restore', 'tracker_restored');
            }
        }
        $GLOBALS['Response']->redirect('/tracker/admin/restore.php');
        break;

    case 'delete':
        // Create field factory
        if ($group = $pm->getProject($request->getValidated('group_id', 'GroupId'))) {
            $atid           = $request->getValidated('atid', 'uint');
            $ath            = new ArtifactType($group, $atid);
            $atf            = new ArtifactTypeFactory($group);
            $art_field_fact = new ArtifactFieldFactory($ath);

            // Then delete all the fields informations
            if (! $art_field_fact->deleteFields($atid)) {
                exit_error($GLOBALS['Language']->getText('global', 'error'), $art_field_fact->getErrorMessage());
            }

            // Then delete all the reports informations
            // Create field factory
            $art_report_fact = new ArtifactReportFactory();

            if (! $art_report_fact->deleteReports($atid)) {
                exit_error($GLOBALS['Language']->getText('global', 'error'), $art_report_fact->getErrorMessage());
            }

            // Delete the artifact type itself
            if (! $atf->deleteArtifactType($atid)) {
                exit_error($GLOBALS['Language']->getText('global', 'error'), $atf->getErrorMessage());
            }
            $feedback = $GLOBALS['Language']->getText('tracker_admin_restore', 'tracker_deleted');
        }
        $GLOBALS['Response']->redirect('/tracker/admin/restore.php');
        break;


    default:
        break;
} // switch
$group = $pm->getProject(1);

$renderer = new AdminPageRenderer();
$renderer->header($GLOBALS['Language']->getText('tracker_admin_restore', 'pending_deletions'));

EventManager::instance()->processEvent(
    Event::LIST_DELETED_TRACKERS,
    []
);

$tracker_list = [];
if (TrackerV3::instance()->available()) {
    $tracker_factory = new ArtifactTypeFactory($group);
    $trackers        = $tracker_factory->getPendingArtifactTypes();

    while ($tracker = db_fetch_array($trackers)) {
        $tracker_list[] = [
            'group_artifact_id' => $tracker['group_artifact_id'],
            'project_name'      => $tracker['project_name'],
            'name'              => $tracker['name'],
            'deletion_date'     => date("Y-m-d", $tracker['deletion_date']),
            'group_id'          => $tracker['group_id'],
        ];
    }

    $tv3_presenter = new ArtifactPendingDeletionPresenter($tracker_list);
    $renderer->renderToPage(
        ForgeConfig::get('codendi_dir') . '/src/templates/admin/trackers',
        'pending-trackers-deletion',
        $tv3_presenter
    );
}

$renderer->footer();
