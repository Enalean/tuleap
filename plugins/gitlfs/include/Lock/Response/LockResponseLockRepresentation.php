<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\Lock\Response;

use DateTimeInterface;
use Tuleap\GitLFS\Lock\Lock;

class LockResponseLockRepresentation implements LockResponse
{
    /**
     * @var Lock
     */
    private $lock;

    public function __construct(Lock $lock)
    {
        $this->lock = $lock;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => (string) $this->lock->getId(),
            'path' => $this->lock->getPath(),
            'locked_at' => $this->lock->getCreationDate()->format(DateTimeInterface::ATOM),
            'owner' => [
                'name' => $this->lock->getOwner()->getRealName(),
            ],
        ];
    }
}
