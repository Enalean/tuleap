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

use GitRepositoryFactory;
use HTTPRequest;
use Tuleap\Git\GitViews\ShowRepo\RepoHeader;
use Tuleap\Layout\BaseLayout;

class GitAdditionalActionEvent implements \Tuleap\Event\Dispatchable
{
    const NAME =  'gitAdditionalAction';
    /**
     * @var HTTPRequest
     */
    private $request;
    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;
    /**
     * @var BaseLayout
     */
    private $layout;
    /**
     * @var RepoHeader
     */
    private $repo_header;

    public function __construct(HTTPRequest $request, BaseLayout $layout, GitRepositoryFactory $repository_factory, RepoHeader $repo_header)
    {
        $this->request            = $request;
        $this->layout             = $layout;
        $this->repository_factory = $repository_factory;
        $this->repo_header        = $repo_header;
    }

    /**
     * @return HTTPRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return GitRepositoryFactory
     */
    public function getRepositoryFactory()
    {
        return $this->repository_factory;
    }

    /**
     * @return BaseLayout
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @return RepoHeader
     */
    public function getRepoHeader()
    {
        return $this->repo_header;
    }
}
