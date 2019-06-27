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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\SVN\REST\v1;

use Tuleap\Project\REST\MinimalProjectRepresentation;
use Tuleap\REST\JsonCast;
use Tuleap\REST\v1\SvnRepositoryRepresentationBase;
use Tuleap\SVN\Repository\Repository;

class RepositoryRepresentation extends SvnRepositoryRepresentationBase
{
    public function build(Repository $repository)
    {
        $project_representation = new MinimalProjectRepresentation();
        $project_representation->buildMinimal($repository->getProject());

        $this->id      = JsonCast::toInt($repository->getId());
        $this->project = $project_representation;
        $this->uri     = self::ROUTE . '/' . $this->id;
        $this->name    = $repository->getName();
        $this->svn_url = $repository->getSvnUrl();
    }
}
