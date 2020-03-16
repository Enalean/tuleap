<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

use Tuleap\Git\GerritCanMigrateChecker;
use Tuleap\Git\GitViews\RepoManagement\Pane;
use Tuleap\Git\GitViews\RepoManagement\Pane\GitViewsRepoManagementPaneCIToken;
use Tuleap\Git\GitViews\RepoManagement\Pane\PanesCollection;
use Tuleap\Git\Notifications\CollectionOfUgroupToBeNotifiedPresenterBuilder;
use Tuleap\Git\Notifications\CollectionOfUserToBeNotifiedPresenterBuilder;
use Tuleap\Git\Notifications\UgroupsToNotifyDao;
use Tuleap\Git\Notifications\UsersToNotifyDao;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRepresentationBuilder;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\Webhook\WebhookDao;
use Tuleap\Git\Webhook\WebhookFactory;

/**
 * Dedicated screen for repo management
 */
class GitViews_RepoManagement
{

    /**
     * @var GitPermissionsManager
     */
    private $git_permission_manager;

    /**
     * @var DefaultFineGrainedPermissionFactory
     */
    private $default_fine_grained_factory;

    /**
     * @var FineGrainedRepresentationBuilder
     */
    private $fine_grained_builder;

    /**
     * @var FineGrainedRetriever
     */
    private $fine_grained_retriever;

    /**
     * @var FineGrainedPermissionFactory
     */
    private $fine_grained_permission_factory;

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

    /**
     * @var GerritCanMigrateChecker
     */
    private $gerrit_can_migrate_checker;
    /**
     * @var RegexpFineGrainedRetriever
     */
    private $regexp_retriever;
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(
        GitRepository $repository,
        Codendi_Request $request,
        Git_Driver_Gerrit_GerritDriverFactory $driver_factory,
        array $gerrit_servers,
        array $gerrit_config_templates,
        Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        GerritCanMigrateChecker $gerrit_can_migrate_checker,
        FineGrainedPermissionFactory $fine_grained_permission_factory,
        FineGrainedRetriever $fine_grained_retriever,
        FineGrainedRepresentationBuilder $fine_grained_builder,
        DefaultFineGrainedPermissionFactory $default_fine_grained_factory,
        GitPermissionsManager $git_permission_manager,
        RegexpFineGrainedRetriever $regexp_retriever,
        EventManager $event_manager,
        ProjectManager $project_manager
    ) {
        $this->project_manager                 = $project_manager;
        $this->repository                      = $repository;
        $this->request                         = $request;
        $this->driver_factory                  = $driver_factory;
        $this->gerrit_servers                  = $gerrit_servers;
        $this->gerrit_config_templates         = $gerrit_config_templates;
        $this->mirror_data_mapper              = $mirror_data_mapper;
        $this->gerrit_can_migrate_checker      = $gerrit_can_migrate_checker;
        $this->fine_grained_permission_factory = $fine_grained_permission_factory;
        $this->fine_grained_retriever          = $fine_grained_retriever;
        $this->fine_grained_builder            = $fine_grained_builder;
        $this->default_fine_grained_factory    = $default_fine_grained_factory;
        $this->git_permission_manager          = $git_permission_manager;
        $this->regexp_retriever                = $regexp_retriever;
        $this->event_manager                   = $event_manager;
        $this->panes                           = $this->buildPanes($repository);
        $this->current_pane                    = 'settings';

        if (isset($this->panes[$request->get('pane')])) {
            $this->current_pane = $request->get('pane');
        }
    }

    /**
     * @return array
     */
    private function buildPanes(GitRepository $repository)
    {
        $collection = new PanesCollection($repository, $this->request);
        $collection->add(new Pane\GeneralSettings($repository, $this->request));

        if ($repository->getBackendType() == GitDao::BACKEND_GITOLITE) {
            $collection->add(
                new Pane\Gerrit(
                    $repository,
                    $this->request,
                    $this->driver_factory,
                    $this->gerrit_can_migrate_checker,
                    $this->gerrit_servers,
                    $this->gerrit_config_templates,
                    $this->project_manager
                )
            );
        }

        $collection->add(new Pane\AccessControl(
            $repository,
            $this->request,
            $this->fine_grained_permission_factory,
            $this->fine_grained_retriever,
            $this->fine_grained_builder,
            $this->default_fine_grained_factory,
            $this->git_permission_manager,
            $this->regexp_retriever
        ));
        $collection->add(new GitViewsRepoManagementPaneCIToken($repository, $this->request));

        $mirrors = $this->mirror_data_mapper->fetchAllForProject($repository->getProject());
        if (count($mirrors) > 0) {
            $repository_mirrors = $this->mirror_data_mapper->fetchAllRepositoryMirrors($repository);
            $collection->add(new Pane\Mirroring($repository, $this->request, $mirrors, $repository_mirrors));
        }

        $webhook_dao                  = new WebhookDao();
        $webhook_factory              = new WebhookFactory($webhook_dao);
        $user_to_be_notified_builder  = new CollectionOfUserToBeNotifiedPresenterBuilder(new UsersToNotifyDao());
        $group_to_be_notified_builder = new CollectionOfUgroupToBeNotifiedPresenterBuilder(
            new UgroupsToNotifyDao()
        );

        $collection->add(new Pane\Notification(
            $repository,
            $this->request,
            $user_to_be_notified_builder,
            $group_to_be_notified_builder
        ));
        $collection->add(new Pane\Hooks($repository, $this->request, $webhook_factory, $webhook_dao));

        $this->event_manager->processEvent($collection);

        $collection->add(new Pane\Delete($repository, $this->request));

        $indexed_panes = array();
        foreach ($collection->getPanes() as $pane) {
            if ($pane->canBeDisplayed()) {
                $indexed_panes[$pane->getIdentifier()] = $pane;
            }
        }
        return $indexed_panes;
    }

    /**
     * Output repo management sub screen to the browser
     */
    public function display()
    {
        echo '<div class="tabbable">';
        echo '<ul class="nav nav-tabs">';
        foreach ($this->panes as $pane) {
            $this->displayTab($pane);
        }
        echo '</ul>';
        echo '<div id="git_repomanagement" class="tab-content git_repomanagement">';
        echo '<div class="tab-pane active">';
        echo $this->panes[$this->current_pane]->getContent();
        echo '</div>';
        echo '</div>';
    }

    private function displayTab(Pane\Pane $pane)
    {
        echo '<li class="' . ($this->current_pane == $pane->getIdentifier() ? 'active' : '') . '">';
        $url = GIT_BASE_URL . '/?' . http_build_query(
            array(
                'action' => 'repo_management',
                'group_id' => $this->repository->getProjectId(),
                'repo_id'  => $this->repository->getId(),
                'pane'     => $pane->getIdentifier(),
            )
        );
        $purifier = Codendi_HTMLPurifier::instance();

        echo '<a href="' . $url . '" title="' . $purifier->purify($pane->getTitle()) . '">' . $purifier->purify($pane->getLabel()) . '</a></li>';
    }
}
