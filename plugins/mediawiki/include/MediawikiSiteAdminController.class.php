<?php
/**
 * Copyright (c) Enalean, 2015 - 2017. All Rights Reserved.
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

use Tuleap\Admin\AdminPageRenderer;

class MediawikiSiteAdminController
{

    private $project_manager;
    private $resource_restrictor;
    private $system_event_manager;
    /**
     * @var \Tuleap\Admin\AdminPageRenderer
     */
    private $admin_page_renderer;
    /**
     * @var MediawikiVersionManager
     */
    private $version_manager;

    public function __construct(AdminPageRenderer $admin_page_renderer)
    {
        $this->project_manager      = ProjectManager::instance();
        $this->resource_restrictor  = new MediawikiSiteAdminResourceRestrictor(
            new MediawikiSiteAdminResourceRestrictorDao(),
            $this->project_manager
        );
        $this->system_event_manager = SystemEventManager::instance();
        $this->admin_page_renderer  = $admin_page_renderer;
        $this->version_manager      = new MediawikiVersionManager(new MediawikiVersionDao());
    }

    public function site_index(HTTPRequest $request)
    {
        $this->assertSiteAdmin($request);

        $presenter = new MediawikiSiteAdminAllowedProjectsPresenter(
            $this->resource_restrictor->searchAllowedProjects(),
            $this->version_manager->countProjectsToMigrateTo123()
        );

        $this->admin_page_renderer->renderAPresenter(
            'Mediawiki',
            ForgeConfig::get('codendi_dir') . '/src/templates/resource_restrictor',
            $presenter::TEMPLATE,
            $presenter
        );
    }

    public function site_update_allow_all_projects(HTTPRequest $request)
    {
        $this->assertSiteAdmin($request);

        $token = new CSRFSynchronizerToken('/plugins/mediawiki/forge_admin.php?action=site_update_allow_all_projects');
        $token->check();

        $this->allowAllProjects();

        $GLOBALS['Response']->redirect('/plugins/mediawiki/forge_admin.php?action=site_index');
    }

    public function site_update_allowed_project_list(HTTPRequest $request)
    {
        $this->assertSiteAdmin($request);

        $token = new CSRFSynchronizerToken('/plugins/mediawiki/forge_admin.php?action=site_update_allowed_project_list');
        $token->check();

        $project_to_add  = $request->get('project-to-allow');
        if ($request->get('allow-project') && ! empty($project_to_add)) {
            $this->allowProject($project_to_add);
        }

        $GLOBALS['Response']->redirect('/plugins/mediawiki/forge_admin.php?action=site_index');
    }

    private function allowAllProjects()
    {
        $this->system_event_manager->createEvent(
            SystemEvent_MEDIAWIKI_SWITCH_TO_123::NAME,
            SystemEvent_MEDIAWIKI_SWITCH_TO_123::ALL,
            SystemEvent::PRIORITY_HIGH
        );

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-mediawiki', 'Submitted project will be converted to Mediawiki 1.23 shortly (check System Events).')
        );
    }

    private function allowProject($project_to_add)
    {
        $project = $this->project_manager->getProjectFromAutocompleter($project_to_add);

        if ($project && ($project->isActive() || $project->isSystem())) {
            $this->system_event_manager->createEvent(
                SystemEvent_MEDIAWIKI_SWITCH_TO_123::NAME,
                $project->getId(),
                SystemEvent::PRIORITY_HIGH
            );

            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-mediawiki', 'Submitted project will be converted to Mediawiki 1.23 shortly (check System Events).')
            );
        } else {
            $this->sendUpdateProjectListError();
        }
    }

    private function sendUpdateProjectListError()
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext('tuleap-mediawiki', 'Something went wrong during the update.')
        );
    }

    private function assertSiteAdmin(HTTPRequest $request)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            $GLOBALS['Response']->redirect('/');
        }
    }
}
