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

declare(strict_types=1);

namespace Tuleap\SVN\AccessControl;

use Tuleap\SVNCore\Repository;

/**
 * @psalm-immutable
 */
class AccessFileHistory
{
    protected $id;
    private $version_number;
    private $content;
    private $version_date;
    /**
     * @var Repository
     */
    private $repository;

    public function __construct(Repository $repository, int $id, int $version_number, string $content, int $version_date)
    {
        $this->id             = $id;
        $this->version_number = $version_number;
        $this->content        = $content;
        $this->version_date   = $version_date;
        $this->repository     = clone $repository;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getVersionNumber()
    {
        return $this->version_number;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getVersionDate()
    {
        return $this->version_date;
    }

    public function getRepository()
    {
        return $this->repository;
    }
}
