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

namespace Tuleap\Tracker\Admin;

use CSRFSynchronizerToken;
use Project;
use TemplateRendererFactory;
use Tracker_IDisplayTrackerLayout;

class GlobalAdminController
{

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf;

    /**
     * @var ArtifactLinksUsageDao
     */
    private $dao;

    /**
     * @var ArtifactLinksUsageUpdater
     */
    private $updater;

    public function __construct(
        ArtifactLinksUsageDao $dao,
        ArtifactLinksUsageUpdater $updater,
        CSRFSynchronizerToken $global_admin_csrf
    ) {
        $this->dao     = $dao;
        $this->updater = $updater;
        $this->csrf    = $global_admin_csrf;
    }

    public function displayGlobalAdministration(Project $project, Tracker_IDisplayTrackerLayout $layout)
    {
        $toolbar     = $this->getToolbar($project);
        $params      = array();
        $breadcrumbs = $this->getAdditionalBreadcrumbs($project);

        $layout->displayHeader(
            $project,
            $GLOBALS['Language']->getText('plugin_tracker', 'trackers'),
            $breadcrumbs,
            $toolbar,
            $params
        );

        $renderer  = TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR);
        $presenter = new GlobalAdminPresenter(
            $project,
            $this->csrf,
            $this->dao->isProjectUsingArtifactLinkTypes($project->getID())
        );

        $renderer->renderToPage(
            'global-admin',
            $presenter
        );

        $layout->displayFooter($project);
    }

    public function updateGlobalAdministration(Project $project)
    {
        $this->csrf->check();
        $this->updater->update($project);
    }

    /**
     * @return string
     */
    public function getTrackerGlobalAdministrationURL(Project $project)
    {
        return TRACKER_BASE_URL . '/?' . http_build_query(array(
                'func'     => 'global-admin',
                'group_id' => $project->getID()
            ));
    }

    /**
     * @return array
     */
    public function getToolbar(Project $project)
    {
        return array(
            array(
                'title' => "Administration",
                'url'   => $this->getTrackerGlobalAdministrationURL($project)
            )
        );
    }

    /**
     * @return array
     */
    private function getAdditionalBreadcrumbs(Project $project)
    {
        return $this->getToolbar($project);
    }
}
