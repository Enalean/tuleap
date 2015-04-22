<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\Git\REST\v1;

use Project;
use PFUser;
use GitRepositoryFactory;
use Tuleap\Git\REST\v1\GitRepositoryRepresentation;

include_once('www/project/admin/permissions.php');

class ProjectResource {

    /** @var RepositoryRepresentationBuilder */
    private $repository_resource_builder;

    /** @var GitRepositoryFactory */
    private $repository_factory;

    public function __construct(GitRepositoryFactory $repository_factory, RepositoryRepresentationBuilder $repository_resource_builder) {
        $this->repository_factory          = $repository_factory;
        $this->repository_resource_builder = $repository_resource_builder;
    }

    public function getGit(Project $project, PFUser $user, $limit, $offset, $fields) {
        $results          = array();
        $git_repositories = $this->repository_factory->getPagninatedRepositoriesUserCanSee(
            $project,
            $user,
            $limit,
            $offset
        );

        foreach($git_repositories as $repository) {
            $results[] = $this->repository_resource_builder->build($user, $repository, $fields);
        }

        return array(
            'repositories' => $results
        );
    }
}