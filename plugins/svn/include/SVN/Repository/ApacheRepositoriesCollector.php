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

declare(strict_types=1);

namespace Tuleap\SVN\Repository;

use Tuleap\SVNCore\GetAllRepositories;
use Tuleap\SVNCore\Repository;

final class ApacheRepositoriesCollector
{
    /**
     * @var RepositoryManager
     */
    private $repository_manager;

    public function __construct(RepositoryManager $repository_manager)
    {
        $this->repository_manager = $repository_manager;
    }

    public function process(GetAllRepositories $get_all_repositories): void
    {
        foreach ($this->repository_manager->getAllRepositoriesInActiveProjects() as $repository) {
            /** @var Repository $repository */
            $get_all_repositories->addRepository(new ApacheConfRepository($repository));
        }
    }
}
