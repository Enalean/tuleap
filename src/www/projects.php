<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
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

use Tuleap\Dashboard\ProjectPresenter;

require_once('pre.php');

$default_content_type = 'text/html';

$project       = null;
$expl_pathinfo = explode('/', $request->getFromServer('REQUEST_URI'));
if (isset($expl_pathinfo[2])) {
    $project = ProjectManager::instance()->getProjectFromAutocompleter(urldecode($expl_pathinfo[2]));
}
if ($project && !$project->isError()) {
    $group_id = $project->getId();

    //set up the group_id
    $_REQUEST['group_id'] = $_GET['group_id'] = $group_id;
    $request = HTTPRequest::instance();
    $request->params['group_id'] = $_REQUEST['group_id'];

    if ($request->isAjax()) {
        header('Content-type: application/json');
        echo json_encode(
            array(
                'id' => $group_id,
                'name' => $project->getPublicName(),
            )
        );
        exit;
    }
    // if the summary service is active we display the dashboard of the project
    // otherwise we display the first active service on the list

    if ($project->usesService('summary')) {
        Tuleap\Instrument\Collect::increment('service.project.summary.accessed');
        if (ForgeConfig::get('sys_use_tlp_in_dashboards')) {
            site_project_header(
                array(
                    'title'  => $project->getUnconvertedPublicName(),
                    'group'  => $project->getID(),
                    'toptab' => 'summary'
                )
            );
            $renderer = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('tuleap_dir'). '/src/templates/dashboard');
            $renderer->renderToPage(
                'project',
                new ProjectPresenter(
                    $project,
                    ProjectManager::instance()
                )
            );
            site_project_footer(array());
            exit;
        }
        //now show the project page
        include_once 'project_home.php';
    } else {
        $val = array_values($project->getServices());
        foreach ($val as $containedSrv) {
            if ($containedSrv->isUsed()) {
                $service = $containedSrv;
                break;
            }
        }
        if ($service->isIFrame()) {
            $label = $service->getLabel();
            if ($label == "service_" . $service->getShortName() . "_lbl_key") {
                $label = $Language->getText('project_admin_editservice', $label);
            } elseif (preg_match('/(.*):(.*)/', $label, $matches)) {
                $label = $Language->getText($matches[1], $matches[2]);
            }
            $title = $label . ' - ' . $project->getPublicName();
            site_project_header(array(
                'title'  => $title,
                'group'  => $request->get('group_id'),
                'toptab' => $service->getId()
            ));
            $GLOBALS['HTML']->iframe($service->getUrl(),
                array('class' => 'iframe_service', 'width' => '100%', 'height' => '650px'));
            site_project_footer(array());
        } else {
            $GLOBALS['Response']->redirect($service->getUrl());
        }
    }
} else {
    header("HTTP/1.0 404 Not Found");
    if (! $request->isAjax()) {
        exit_no_group();
    }
}
