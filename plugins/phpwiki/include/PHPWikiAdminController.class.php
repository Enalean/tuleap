<?php
use Tuleap\Admin\AdminPageRenderer;

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

class PHPWikiAdminController {

    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var PHPWikiAdminMigrator
     */
    private $wiki_migrator;
    /**
     * @var SystemEventManager
     */
    private $system_event_manager;
    /**
     * @var AdminPageRenderer
     */
    private $admin_page_renderer;

    public function __construct(AdminPageRenderer $admin_page_renderer)
    {
        $this->project_manager      = ProjectManager::instance();
        $this->wiki_migrator        = new PHPWikiAdminMigrator(
            new PHPWikiAdminMigratorDao(),
            $this->project_manager
        );
        $this->system_event_manager = SystemEventManager::instance();
        $this->admin_page_renderer  = $admin_page_renderer;
    }

    public function getAdminIndex(HTTPRequest $request)
    {
        $this->checkAccess($request);

        $presenter = new PHPWikiAdminAllowedProjectsPresenter(
            $this->wiki_migrator->searchProjectsUsingPlugin()
        );

        $this->admin_page_renderer->renderAPresenter(
            'PHPWiki',
            ForgeConfig::get('codendi_dir') . '/src/templates/resource_restrictor',
            $presenter::TEMPLATE,
            $presenter
        );
    }

    public function updateProject(HTTPRequest $request) {
        $this->checkAccess($request);

        $token = new CSRFSynchronizerToken('/plugins/phpwiki/admin.php?action=update_project');
        $token->check();

        $project_to_add  = $request->get('project-to-allow');
        if ($request->get('allow-project') && !empty($project_to_add)) {
            $this->migrateProject($project_to_add);
        }

        $GLOBALS['Response']->redirect('/plugins/phpwiki/admin.php?action=index');
    }

    private function migrateProject($project_to_migrate) {
        $project = $this->project_manager->getProjectFromAutocompleter($project_to_migrate);

        if ($project && $this->wiki_migrator->canMigrate($project)) {
            $this->system_event_manager->createEvent(
                SystemEvent_PHPWIKI_SWITCH_TO_PLUGIN::NAME,
                $project->getId(),
                SystemEvent::PRIORITY_HIGH
            );

            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('plugin_phpwiki', 'allowed_project_allow_project')
            );
        } else {
            $this->sendUpdateProjectListError();
        }
    }

    private function sendUpdateProjectListError() {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $GLOBALS['Language']->getText('plugin_phpwiki', 'allowed_project_update_project_list_error')
        );
    }

    private function checkAccess(HTTPRequest $request) {
        if (! $request->getCurrentUser()->isSuperUser()) {
            $GLOBALS['Response']->redirect('/');
        }
    }
}
