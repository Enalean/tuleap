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

namespace Tuleap\Git\PermissionsPerGroup;

use GitRepositoryFactory;
use Project;
use Tuleap\Git\Permissions\FineGrainedRetriever;

class RepositoriesPermissionRepresentationBuilder
{
    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;

    /**
     * @var RepositoryFineGrainedRepresentationBuilder
     */
    private $fined_grained_representation_builder;
    /**
     * @var RepositorySimpleRepresentationBuilder
     */
    private $repository_simple_representation_builder;
    /**
     * @var FineGrainedRetriever
     */
    private $fine_grained_retriever;

    public function __construct(
        RepositoryFineGrainedRepresentationBuilder $fined_grained_representation_builder,
        RepositorySimpleRepresentationBuilder $repository_simple_representation_builder,
        GitRepositoryFactory $repository_factory,
        FineGrainedRetriever $fine_grained_retriever
    ) {
        $this->fined_grained_representation_builder     = $fined_grained_representation_builder;
        $this->repository_simple_representation_builder = $repository_simple_representation_builder;
        $this->repository_factory                       = $repository_factory;
        $this->fine_grained_retriever                   = $fine_grained_retriever;
    }

    public function build(Project $project, $selected_ugroup_id)
    {
        $repositories = $this->repository_factory->getAllRepositoriesOfProject($project);

        $repositories_representation = [];
        foreach ($repositories as $repository) {
            if ($this->fine_grained_retriever->doesRepositoryUseFineGrainedPermissions($repository)) {
                $repository_representation = $this->fined_grained_representation_builder->build(
                    $repository,
                    $project,
                    $selected_ugroup_id
                );
            } else {
                $repository_representation = $this->repository_simple_representation_builder->build(
                    $repository,
                    $project,
                    $selected_ugroup_id
                );
            }

            if ($repository_representation) {
                $repositories_representation[] = $repository_representation;
            }
        }

        return new RepositoriesPermissionRepresentation($repositories_representation);
    }
}
