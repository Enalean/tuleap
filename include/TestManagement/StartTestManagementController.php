<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use BackendLogger;
use HTTPRequest;
use Project;
use TrackerFactory;
use TrackerXmlImport;
use Tuleap\TestManagement\Breadcrumbs\NoCrumb;
use Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\AllowedProjectsConfig;

class StartTestManagementController
{
    /**
     * @var BackendLogger
     */
    private $backend_logger;

    /**
     * @var TrackerXmlImport
     */
    private $tracker_xml_import;

    /**
     * @var AllowedProjectsConfig
     */
    private $allowed_projects_config;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var ArtifactLinksUsageUpdater
     */
    private $artifact_link_usage_updater;

    public function __construct(
        TrackerFactory        $tracker_factory,
        BackendLogger         $backend_logger,
        TrackerXmlImport      $tracker_xml_import,
        AllowedProjectsConfig $allowed_projects_config,
        ArtifactLinksUsageUpdater $artifact_link_usage_updater
    ) {
        $this->tracker_factory             = $tracker_factory;
        $this->tracker_xml_import          = $tracker_xml_import;
        $this->backend_logger              = $backend_logger;
        $this->allowed_projects_config     = $allowed_projects_config;
        $this->artifact_link_usage_updater = $artifact_link_usage_updater;
    }

    public function misconfiguration(HTTPRequest $request)
    {
        $current_user   = $request->getCurrentUser();
        $project_id     = $request->getProject()->getID();
        $is_user_admin  = $current_user->isAdmin($project_id);

        return $this->getRenderer()->renderToString(
            'misconfiguration',
            new StartTestManagementPresenter(
                $is_user_admin
            )
        );
    }

    public function createConfig(\HTTPRequest $request)
    {
        $config  = new Config(new Dao());
        $project = $request->getProject();

        $config_creator = new FirstConfigCreator(
            $config,
            $this->tracker_factory,
            $this->tracker_xml_import,
            $this->backend_logger
        );

        $config_creator->createConfigForProjectFromXML($project);

        $this->allowProjectToUseNature(
            $project,
            $project
        );
    }

    public function getBreadcrumbs() {
        return new NoCrumb();
    }

    private function allowProjectToUseNature(
        Project $template,
        Project $project
    ) {
        if (! $this->allowed_projects_config->isProjectAllowedToUseNature($template)) {
            $this->allowed_projects_config->addProject($project);
        }

        $this->artifact_link_usage_updater->forceUsageOfArtifactLinkTypes($project);
    }

    private function getRenderer() {
        $templates_path = join(
            '/',
            array(
                TESTMANAGEMENT_BASE_DIR,
                'templates'
            )
        );

        return \TemplateRendererFactory::build()->getRenderer($templates_path);
    }
}
