<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\SVN\REST\v1;

use Project;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\Repository\RepositoryPaginatedCollection;

class ProjectResource
{
    /**
     * @var RepositoryManager
     */
    private $repository_manager;

    public function __construct(RepositoryManager $repository_manager)
    {
        $this->repository_manager = $repository_manager;
    }

    /**
     * @return RepositoryRepresentationPaginatedCollection
     */
    public function getRepositoryCollection(Project $project, $filter, $limit, $offset)
    {
        if ($filter) {
            return $this->getCollectionWithFilter($project, $filter, $limit, $offset);
        }

        return $this->getCollection($project, $limit, $offset);
    }

    /**
     * @return RepositoryRepresentationPaginatedCollection
     */
    private function getCollectionWithFilter(Project $project, $filter, $limit, $offset)
    {
        $svn_repositories_collection = $this->repository_manager->getRepositoryPaginatedCollectionByName(
            $project,
            $filter,
            $limit,
            $offset
        );

        return $this->buildCollection($svn_repositories_collection);
    }

    /**
     * @return RepositoryRepresentationPaginatedCollection
     */
    private function getCollection(Project $project, $limit, $offset)
    {
        $svn_repositories_collection = $this->repository_manager->getRepositoryPaginatedCollection(
            $project,
            $limit,
            $offset
        );

        return $this->buildCollection($svn_repositories_collection);
    }

    /**
     * @return RepositoryRepresentationPaginatedCollection
     */
    private function buildCollection(RepositoryPaginatedCollection $svn_repositories_collection)
    {
        return new RepositoryRepresentationPaginatedCollection(
            $this->getRepresentations($svn_repositories_collection->getRepositories()),
            $svn_repositories_collection->getTotalSize()
        );
    }

    /**
     * @return RepositoryRepresentation[]
     */
    private function getRepresentations(array $svn_repositories)
    {
        $representations = [];

        foreach ($svn_repositories as $repository) {
            $representation = RepositoryRepresentation::build($repository);

            $representations[] = $representation;
        }

        return $representations;
    }
}
