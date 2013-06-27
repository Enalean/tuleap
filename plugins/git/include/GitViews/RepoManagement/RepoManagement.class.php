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

    /**
     * @var array
     */
    private $gerrit_servers;

    /** @var Git_Driver_Gerrit */
    private $driver;

    public function __construct(GitRepository $repository, Codendi_Request $request, Git_Driver_Gerrit $driver, array $gerrit_servers) {
        $this->repository     = $repository;
        $this->request        = $request;
        $this->driver         = $driver;
        $this->gerrit_servers = $gerrit_servers;
        $this->panes          = $this->buildPanes($repository);
        $this->current_pane   = 'settings';
        if (isset($this->panes[$request->get('pane')])) {
            $this->current_pane = $request->get('pane');
        }
    }

    /**
     * @return array
     */
    private function buildPanes(GitRepository $repository) {
        $panes = array(
            new GitViews_RepoManagement_Pane_GeneralSettings($repository, $this->request),
            new GitViews_RepoManagement_Pane_Gerrit($repository, $this->request, $this->driver, $this->gerrit_servers),
            new GitViews_RepoManagement_Pane_AccessControl($repository, $this->request),
            new GitViews_RepoManagement_Pane_Notification($repository, $this->request),
            new GitViews_RepoManagement_Pane_Delete($repository, $this->request),
        );
        $indexed_panes = array();
        foreach ($panes as $pane) {
            if ($pane->canBeDisplayed()) {
                $indexed_panes[$pane->getIdentifier()] = $pane;
            }
        }
        return $indexed_panes;
    }

    /**
     * Output repo management sub screen to the browser
     */
    public function display() {
        echo '<div class="tabbable tabs-left">';
        echo '<ul class="nav nav-tabs">';
        foreach ($this->panes as $pane) {
            $this->displayTab($pane);
        }
        echo '</ul>';
        echo '<div id="git_repomanagement" class="tab-content">';
        echo '<div class="tab-pane active">';
        echo $this->panes[$this->current_pane]->getContent();
        echo '</div>';
        echo '</div>';
    }

    private function displayTab($pane) {
        echo '<li class="'. ($this->current_pane == $pane->getIdentifier() ? 'active' : '') .'">';
        $url = GIT_BASE_URL .'/?'. http_build_query(
            array(
                'action' => 'repo_management',
                'group_id' => $this->repository->getProjectId(),
                'repo_id'  => $this->repository->getId(),
                'pane'     => $pane->getIdentifier(),
            )
        );
        echo '<a href="'. $url .'">'. $pane->getTitle() .'</a></li>';
    }
}
?>
