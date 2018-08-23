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
 *
 */

namespace Tuleap\Git;

use HTTPRequest;
use Tuleap\Git\GitViews\ShowRepo\RepoHeader;

class GitAdditionalActionEvent implements \Tuleap\Event\Dispatchable
{
    const NAME = 'gitAdditionalAction';
    /**
     * @var HTTPRequest
     */
    private $request;

    /**
     * @var RepoHeader
     */
    private $repo_header;

    public function __construct(HTTPRequest $request, RepoHeader $repo_header)
    {
        $this->request     = $request;
        $this->repo_header = $repo_header;
    }

    /**
     * @return HTTPRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return RepoHeader
     */
    public function getRepoHeader()
    {
        return $this->repo_header;
    }
}
