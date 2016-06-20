<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

require_once 'MediawikiSiteAdminAllowedProjectsPresenter.class.php';
require_once 'MediawikiSiteAdminResourceRestrictor.php';
require_once 'events/SytemEvent_MEDIAWIKI_SWITCH_TO_123.class.php';

class MediawikiSiteAdminController {

    private $project_manager;
    private $resource_restrictor;
    private $system_event_manager;

    public function __construct() {
        $this->project_manager     = ProjectManager::instance();
        $this->resource_restrictor = new MediawikiSiteAdminResourceRestrictor(
            new MediawikiSiteAdminResourceRestrictorDao(),
            $this->project_manager
        );
        $this->system_event_manager = SystemEventManager::instance();
    }

    public function site_index(HTTPRequest $request) {
        $this->assertSiteAdmin($request);

        $presenter = new MediawikiSiteAdminAllowedProjectsPresenter(
                $this->resource_restrictor->searchAllowedProjects()
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/resource_restrictor');

        $GLOBALS['HTML']->header(array('title'=>'Mediawiki', 'selected_top_tab' => 'admin', 'main_classes' => array('framed')));
        $renderer->renderToPage($presenter::TEMPLATE, $presenter);
        $GLOBALS['HTML']->footer(array());
    }

    public function site_update_allowed_project_list(HTTPRequest $request) {
        $this->assertSiteAdmin($request);

        $token = new CSRFSynchronizerToken('/plugins/mediawiki/forge_admin?action=site_update_allowed_project_list');
        $token->check();

        $project_to_add  = $request->get('project-to-allow');
        if ($request->get('allow-project') && !empty($project_to_add)) {
            $this->allowProject($project_to_add);
        }

        $GLOBALS['Response']->redirect('/plugins/mediawiki/forge_admin?action=site_index');
    }

    private function allowProject($project_to_add) {
        $project = $this->project_manager->getProjectFromAutocompleter($project_to_add);

        if ($project && $this->resource_restrictor->allowProject($project)) {
            $this->system_event_manager->createEvent(
                SystemEvent_MEDIAWIKI_SWITCH_TO_123::NAME,
                $project->getId(),
                SystemEvent::PRIORITY_HIGH
            );

            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('plugin_mediawiki', 'allowed_project_allow_project')
            );
        } else {
            $this->sendUpdateProjectListError();
        }
    }

    private function sendUpdateProjectListError() {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $GLOBALS['Language']->getText('plugin_mediawiki', 'allowed_project_update_project_list_error')
        );
    }

    private function assertSiteAdmin(HTTPRequest $request) {
        if (! $request->getCurrentUser()->isSuperUser()) {
            $GLOBALS['Response']->redirect('/');
        }
    }
}
