<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\User\AccessKey;

class AccessKeyMetadata
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var \DateTimeImmutable
     */
    private $creation_date;
    /**
     * @var string
     */
    private $description;
    /**
     * @var \DateTimeImmutable
     */
    private $last_used_date;
    /**
     * @var null|string
     */
    private $last_used_ip;

    /**
     * @var \DateTimeImmutable|null
     */
    private $expiration_date;

    public function __construct(
        $id,
        \DateTimeImmutable $creation_date,
        $description,
        ?\DateTimeImmutable $last_used_date = null,
        $last_used_ip = null,
        ?\DateTimeImmutable $expiration_date = null
    ) {

        $this->id               = $id;
        $this->creation_date    = $creation_date;
        $this->expiration_date  = $expiration_date;
        $this->description      = $description;
        $this->last_used_date   = $last_used_date;
        $this->last_used_ip     = $last_used_ip;
    }

    public function getID()
    {
        return $this->id;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getCreationDate()
    {
        return $this->creation_date;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return null|\DateTimeImmutable
     */
    public function getLastUsedDate()
    {
        return $this->last_used_date;
    }

    /**
     * @return null|string
     */
    public function getLastUsedIP()
    {
        return $this->last_used_ip;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getExpirationDate(): ?\DateTimeImmutable
    {
        return $this->expiration_date;
    }
}
