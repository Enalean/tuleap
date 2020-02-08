<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git\Repository;

final class LargestObjectSizeGitRepository
{
    /**
     * @var \GitRepository
     */
    private $repository;
    /**
     * @var int
     */
    private $largest_size;

    public function __construct(\GitRepository $repository, int $largest_size)
    {
        $this->repository   = $repository;
        $this->largest_size = $largest_size;
    }

    public function getRepository() : \GitRepository
    {
        return $this->repository;
    }

    public function getLargestObjectSize() : int
    {
        return $this->largest_size;
    }

    public function isOverTheObjectSizeLimit() : bool
    {
        return $this->largest_size > \Git_Gitolite_ProjectSerializer::OBJECT_SIZE_LIMIT;
    }
}
