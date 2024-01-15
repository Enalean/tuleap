<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST;

use Tuleap\Git\REST\v1\GitRepositoryRepresentation;
use Tuleap\PullRequest\REST\v1\PullRequestInlineCommentsResource;
use Tuleap\PullRequest\REST\v1\PullRequestCommentsResource;
use Tuleap\PullRequest\REST\v1\PullRequestRepresentation;
use Tuleap\PullRequest\REST\v1\RepositoryResource;

/**
 * Inject resource into restler
 */
class ResourcesInjector
{
    public function populate(\Luracast\Restler\Restler $restler)
    {
        $restler->addAPIClass('\\Tuleap\\PullRequest\\REST\\v1\\PullRequestsResource', PullRequestRepresentation::ROUTE);
        $restler->addAPIClass(PullRequestCommentsResource::class, PullRequestCommentsResource::ROUTE);
        $restler->addAPIClass(PullRequestInlineCommentsResource::class, PullRequestInlineCommentsResource::ROUTE);
        $restler->addAPIClass(RepositoryResource::class, GitRepositoryRepresentation::ROUTE);
    }
}
