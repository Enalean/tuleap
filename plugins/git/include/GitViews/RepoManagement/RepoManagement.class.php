<?php
/**
 * Copyright (c) Enalean, 2012 - 2015. All Rights Reserved.
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
     * @var Git_RemoteServer_GerritServer[]
     */
    private $gerrit_servers;

    /** @var Git_Driver_Gerrit_GerritDriverFactory */
    private $driver_factory;

    /** @var Git_Driver_Gerrit_Template_Template[] */
    private $gerrit_config_templates;

    /** @var Git_Mirror_MirrorDataMapper */
    private $mirror_data_mapper;

    public function __construct(
        GitRepository $repository,
        Codendi_Request $request,
        Git_Driver_Gerrit_GerritDriverFactory $driver_factory,
        array $gerrit_servers,
        array $gerrit_config_templates,
        Git_Mirror_MirrorDataMapper $mirror_data_mapper
    ) {
        $this->repository              = $repository;
        $this->request                 = $request;
        $this->driver_factory          = $driver_factory;
        $this->gerrit_servers          = $gerrit_servers;
        $this->gerrit_config_templates = $gerrit_config_templates;
        $this->mirror_data_mapper      = $mirror_data_mapper;
        $this->panes                   = $this->buildPanes($repository);
        $this->current_pane            = 'settings';
        if (isset($this->panes[$request->get('pane')])) {
            $this->current_pane = $request->get('pane');
        }

    }

    /**
     * @return array
     */
    private function buildPanes(GitRepository $repository) {
        $panes = array(new GitViews_RepoManagement_Pane_GeneralSettings($repository, $this->request));

        if ($repository->getBackendType() == GitDao::BACKEND_GITOLITE) {
            $panes[] = new GitViews_RepoManagement_Pane_Gerrit($repository, $this->request, $this->driver_factory, $this->gerrit_servers, $this->gerrit_config_templates);
        }

        $panes[] = new GitViews_RepoManagement_Pane_AccessControl($repository, $this->request);

        $mirrors = $this->mirror_data_mapper->fetchAllForProject($repository->getProject());
        if (count($mirrors) > 0) {
            $repository_mirrors = $this->mirror_data_mapper->fetchAllRepositoryMirrors($repository);
            $panes[]            = new GitViews_RepoManagement_Pane_Mirroring($repository, $this->request, $mirrors, $repository_mirrors);
        }

        $panes[] = new GitViews_RepoManagement_Pane_Notification($repository, $this->request);
        $panes[] = new GitViews_RepoManagement_Pane_Hooks($repository, $this->request);
        $panes[] = new GitViews_RepoManagement_Pane_Delete($repository, $this->request);

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
