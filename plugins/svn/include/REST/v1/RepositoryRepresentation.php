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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\SVN\REST\v1;

use Project;
use Tuleap\Project\REST\MinimalProjectRepresentation;
use Tuleap\REST\JsonCast;
use Tuleap\REST\v1\SvnRepositoryRepresentationBase;
use Tuleap\SVNCore\Repository;

/**
 * @psalm-immutable
 */
class RepositoryRepresentation extends SvnRepositoryRepresentationBase
{
    protected function __construct(Project $project, int $id, string $name, string $svn_url)
    {
        parent::__construct();
        $project_representation = new MinimalProjectRepresentation($project);

        $this->id      = JsonCast::toInt($id);
        $this->project = $project_representation;
        $this->uri     = self::ROUTE . '/' . $this->id;
        $this->name    = $name;
        $this->svn_url = $svn_url;
    }

    public static function build(Repository $repository): RepositoryRepresentation
    {
        return new self($repository->getProject(), JsonCast::toInt($repository->getId()), $repository->getName(), $repository->getSvnUrl());
    }
}
