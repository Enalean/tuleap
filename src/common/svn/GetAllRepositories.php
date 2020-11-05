<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\SVN;

use Tuleap\Event\Dispatchable;

final class GetAllRepositories implements Dispatchable
{
    public const NAME = 'getAllRepositories';
    /**
     * @var \SVN_DAO
     */
    private $dao;
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var ApacheConfRepository[]
     */
    private $repositories = [];

    public function __construct(\SVN_DAO $dao, \ProjectManager $project_manager)
    {
        $this->dao = $dao;
        $this->project_manager = $project_manager;
    }

    /**
     * @return ApacheConfRepository[]
     */
    public function getRepositories(): array
    {
        foreach ($this->dao->searchSvnRepositories() as $row) {
            $project = $this->project_manager->getProjectFromDbRow($row);
            $repository = new CoreApacheConfRepository($project);
            if (! isset($this->repositories[$repository->getURLPath()])) {
                $this->repositories[$repository->getURLPath()] = $repository;
            }
        }
        return $this->repositories;
    }

    public function addRepository(ApacheConfRepository $repository): void
    {
        $this->repositories[$repository->getURLPath()] = $repository;
    }
}
