<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Admin\AdminPageRenderer;

class Git_AdminGitoliteConfig {

    const ACTION = 'update_config';

    /**
     * @var Git_SystemEventManager
     */
    private $system_event_manager;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf;

    /** @var AdminPageRenderer */
    private $admin_page_renderer;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        ProjectManager $project_manager,
        Git_SystemEventManager $system_event_manager,
        AdminPageRenderer $admin_page_renderer
    ) {
        $this->csrf                 = $csrf;
        $this->project_manager      = $project_manager;
        $this->system_event_manager = $system_event_manager;
        $this->admin_page_renderer  = $admin_page_renderer;
    }

    public function process(Codendi_Request $request) {
        $action = $request->get('action');

        if ($action === false) {
            return;
        }

        if ($action !== self::ACTION) {
            $GLOBALS['Response']->addFeedback(
                'error',
                $GLOBALS['Language']->getText('plugin_git', 'regenerate_config_bad_request')
            );
            return;
        }

        $this->csrf->check();
        $project = $this->getProject($request->get('gitolite_config_project'));

        if (! $project) {
            $GLOBALS['Response']->addFeedback(
                'error',
                $GLOBALS['Language']->getText('plugin_git', 'regenerate_config_project_not_exist')
            );
            return;
        }

        $this->system_event_manager->queueRegenerateGitoliteConfig($project->getID());

        $GLOBALS['Response']->addFeedback(
            'info',
            $GLOBALS['Language']->getText('plugin_git', 'regenerate_config_waiting', array($project->getPublicName()))
        );
        return true;
    }

    /**
     * @return Project
     */
    private function getProject($project_name_from_autocomplete) {
        return $this->project_manager->getProjectFromAutocompleter($project_name_from_autocomplete);
    }

    public function display(Codendi_Request $request) {
        $title    = $GLOBALS['Language']->getText('plugin_git', 'descriptor_name');
        $template_path = dirname(GIT_BASE_DIR).'/templates';

        $admin_presenter = new Git_AdminGitoliteConfigPresenter(
            $title,
            $this->csrf
        );

        $this->admin_page_renderer->renderANoFramedPresenter(
            $title,
            $template_path,
            'admin-plugin',
            $admin_presenter
        );
    }
}
