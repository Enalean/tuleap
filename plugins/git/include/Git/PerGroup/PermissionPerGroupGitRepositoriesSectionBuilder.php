<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\PerGroup;

use GitRepository;
use GitRepositoryFactory;
use Project;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Project\Admin\Permission\PermissionPerGroupPaneCollector;
use UGroupManager;

class PermissionPerGroupGitRepositoriesSectionBuilder
{
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;
    /**
     * @var FineGrainedRetriever
     */
    private $fine_grained_retriever;

    /**
     * @var FineGrainedPermissionsPresenterBuilder
     */
    private $fine_grained_builder;
    /**
     * @var SimplePermissionsPresenterBuilder
     */
    private $simple_builder;

    public function __construct(
        FineGrainedRetriever $fine_grained_retriever,
        UGroupManager $ugroup_manager,
        GitRepositoryFactory $repository_factory,
        SimplePermissionsPresenterBuilder $simple_builder,
        FineGrainedPermissionsPresenterBuilder $fine_grained_builder
    ) {
        $this->fine_grained_retriever = $fine_grained_retriever;
        $this->ugroup_manager         = $ugroup_manager;
        $this->repository_factory     = $repository_factory;
        $this->simple_builder         = $simple_builder;
        $this->fine_grained_builder   = $fine_grained_builder;
    }

    public function buildPresenter(PermissionPerGroupPaneCollector $event)
    {
        $project            = $event->getProject();
        $selected_ugroup_id = $event->getSelectedUGroupId();
        $repositories       = $this->repository_factory->getAllRepositoriesOfProject($project);
        $has_repositories   = count($repositories) > 0;

        $repository_presenters = new RepositoryPermissionsPresenterCollection();
        foreach ($repositories as $repository) {
            $this->buildRepositoryPresenter($repository_presenters, $repository, $project, $selected_ugroup_id);
        }

        $selected_ugroup = $this->ugroup_manager->getUGroup($event->getProject(), $selected_ugroup_id);

        return new RepositoriesSectionPresenter(
            $repository_presenters->getPresenters(),
            $has_repositories,
            $selected_ugroup
        );
    }

    private function buildRepositoryPresenter(
        RepositoryPermissionsPresenterCollection $collection,
        GitRepository $repository,
        Project $project,
        $selected_ugroup_id
    ) {
        if ($this->fine_grained_retriever->doesRepositoryUseFineGrainedPermissions($repository)) {
            $this->fine_grained_builder->addPresenterToCollection($collection, $repository, $project, $selected_ugroup_id);
            return;
        }

        $this->simple_builder->addPresenterToCollection($collection, $repository, $project, $selected_ugroup_id);
    }
}
