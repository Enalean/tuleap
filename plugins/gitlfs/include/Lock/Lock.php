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

namespace Tuleap\GitLFS\Lock;

use DateTimeImmutable;
use PFUser;

class Lock
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $path;

    /**
     * @var PFUser
     */
    private $owner;

    /**
     * @var DateTimeImmutable
     */
    private $creation_date;

    public function __construct(
        int $id,
        string $path,
        PFUser $owner,
        int $creation_timestamp
    ) {
        $this->id            = $id;
        $this->path          = $path;
        $this->owner         = $owner;
        $this->creation_date = (new DateTimeImmutable())->setTimestamp($creation_timestamp);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getCreationDate(): DateTimeImmutable
    {
        return $this->creation_date;
    }

    public function getOwner(): PFUser
    {
        return $this->owner;
    }
}
