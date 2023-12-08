<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

use Project;
use Tuleap\SVNCore\Repository;

class RepositoryByProjectCollection
{
    /**
     * @var Project
     */
    private $project;
    /**
     * @var Repository[]
     */
    private $repository_list;

    private function __construct(Project $project, array $repository_list)
    {
        $this->project         = $project;
        $this->repository_list = $repository_list;
    }

    /**
     * @param Repository[] $repository_list
     */
    public static function build(Project $project, array $repository_list): self
    {
        return new self($project, $repository_list);
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @return Repository[]
     */
    public function getRepositoryList(): array
    {
        return $this->repository_list;
    }
}
