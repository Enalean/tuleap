<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * Controller for site admin views
 */
class FullTextSearch_Controller_Admin extends MVC2_PluginController {

    public function __construct(Codendi_Request $request) {
        parent::__construct('fulltextsearch', $request);
    }

    public function index() {
        $project_manager    = ProjectManager::instance();
        $project_presenters = $this->getProjectPresenters($project_manager->getProjectsByStatus(Project::STATUS_ACTIVE));

        $GLOBALS['HTML']->header(array('title' => $GLOBALS['Language']->getText('plugin_fulltextsearch', 'admin_title')));
        $this->renderer->renderToPage('admin', new FullTextSearch_Presenter_AdminPresenter($project_presenters));
        $GLOBALS['HTML']->footer(array());
    }

    public function reindex($group_id) {
        $project = $this->request->getProject();

        $this->addFeedback('info', $GLOBALS['Language']->getText('plugin_fulltextsearch', 'waiting_for_reindexation', array(util_unconvert_htmlspecialchars($project->getPublicName()))));
        $this->index();
    }

    private function getProjectPresenters($projects) {
        $presenters = array();
        foreach ($projects as $project) {
            $presenters[] = new FullTextSearch_Presenter_ProjectPresenter($project);
        }

        return $presenters;
    }
}