<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__ . '/../include/pre.php';

$request = HTTPRequest::instance();

$pm      = ProjectManager::instance();
$project = $pm->getProject($request->get('group_id'));
if ($project && $request->exist('id')) {
    $db_res = db_query("SELECT *
        FROM service
        WHERE group_id   = " . (int) $request->get('group_id') . "
          AND service_id = " . (int) $request->get('id') . "
          AND is_used    = 1");
    if (db_numrows($db_res) && $service = db_fetch_array($db_res)) {
        if ($service['is_in_iframe']) {
            $label = $service['label'];
            if ($label == "service_" . $service['short_name'] . "_lbl_key") {
                $label = $Language->getOverridableText('project_admin_editservice', $label);
            } elseif (preg_match('/(.*):(.*)/', $label, $matches)) {
                $label = $Language->getOverridableText($matches[1], $matches[2]);
            }
            $title = $label . ' - ' . $project->getPublicName();
            site_project_header($project, \Tuleap\Layout\HeaderConfigurationBuilder::get($title)->inProject($project, $service['service_id'])->build());
            $GLOBALS['HTML']->iframe($service['link'], ['class' => 'iframe_service', 'width' => '100%', 'height' => '650px']);
            site_project_footer([]);
        } else {
            $GLOBALS['Response']->redirect($service['link']);
        }
        exit();
    }
}
$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'missing_parameters'));
$GLOBALS['Response']->redirect('/');
