<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Test\DB;

use Ramsey\Uuid\Rfc4122\UuidV7;
use Ramsey\Uuid\UuidInterface;
use Tuleap\DB\UUID;

/**
 * @psalm-immutable
 */
final class UUIDTestContext implements UUID
{
    private UuidInterface $uuid;

    public function __construct()
    {
        $this->uuid = UuidV7::uuid7();
    }

    #[\Override]
    public function getBytes(): string
    {
        return $this->uuid->getBytes();
    }

    #[\Override]
    public function toString(): string
    {
        return $this->uuid->toString();
    }
}
