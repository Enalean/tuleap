<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
use Tuleap\Svn\Repository\RepositoryManager;

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

    public function getSvn(Project $project, $limit, $offset)
    {
        $results          = array();
        $svn_repositories = $this->repository_manager->getPagninatedRepositories(
            $project,
            $limit,
            $offset
        );

        foreach ($svn_repositories as $repository) {
            $representation = new RepositoryRepresentation();
            $representation->build($repository);

            $results[] = $representation;
        }

        return array(
            'repositories' => $results
        );
    }
}
