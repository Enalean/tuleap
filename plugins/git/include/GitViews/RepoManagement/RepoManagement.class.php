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

require_once GIT_BASE_DIR .'/GitRepository.class.php';
require_once 'Pane/GeneralSettings.class.php';
require_once 'Pane/AccessControl.class.php';
require_once 'Pane/NotificationPrefix.class.php';
require_once 'Pane/NotificatedPeople.class.php';
require_once 'Pane/Delete.class.php';

/**
 * Dedicated screen for repo management
 */
class GitViews_RepoManagement {

    /**
     * @var GitRepository
     */
    private $repository;

    /**
     * @var Codendi_Request
     */
    private $request;

    public function __construct(GitRepository $repository, Codendi_Request $request) {
        $this->repository   = $repository;
        $this->request      = $request;
        $this->panes        = $this->buildPanes($repository);
        $this->current_pane = 'settings';
        if (isset($this->panes[$request->get('pane')])) {
            $this->current_pane = $request->get('pane');
        }
    }

    /**
     * @return array
     */
    private function buildPanes(GitRepository $repository) {
        $panes = array(
            new GitViews_RepoManagement_Pane_GeneralSettings($repository),
            new GitViews_RepoManagement_Pane_AccessControl($repository),
            new GitViews_RepoManagement_Pane_NotificationPrefix($repository),
            new GitViews_RepoManagement_Pane_NotificatedPeople($repository),
            new GitViews_RepoManagement_Pane_Delete($repository),
        );
        $indexed_panes = array();
        foreach ($panes as $pane) {
            $indexed_panes[$pane->getIdentifier()] = $pane;
        }
        return $indexed_panes;
    }

    /**
     * Output repo management sub screen to the browser
     */
    public function display() {
        echo '<div class="tabbable tabs-left">';
        echo '<ul class="nav nav-tabs">';
        foreach ($this->panes as $key => $pane) {
            echo '<li class="'. ($this->current_pane == $key ? 'active' : '') .'">';
            echo '<a href="/plugins/git/?action=repo_management&group_id=102&repo_id=50&pane='. $key .'">'. $pane->getTitle() .'</a></li>';
        }
        echo '</ul>';
        echo '<div id="git_repomanagement" class="tab-content">';
        echo '<div class="tab-pane active">';
        echo $this->panes[$this->current_pane]->getContent();
        echo '</div>';
        echo '</div>';
    }
}
?>
