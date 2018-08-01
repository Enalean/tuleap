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

namespace Tuleap\Git\GitViews\RepoManagement\Pane;

use Codendi_Request;
use GitRepository;
use Tuleap\Event\Dispatchable;

class PanesCollection implements Dispatchable
{
    const NAME = 'collectPanes';

    /**
     * @var Pane[]
     */
    private $panes = [];
    /**
     * @var GitRepository
     */
    private $repository;
    /**
     * @var Codendi_Request
     */
    private $request;

    public function __construct(GitRepository $repository, Codendi_Request $request)
    {
        $this->repository = $repository;
        $this->request    = $request;
    }

    public function add(Pane $pane)
    {
        $this->panes[] = $pane;
    }

    public function getPanes()
    {
        return $this->panes;
    }

    /**
     * @return GitRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @return Codendi_Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
