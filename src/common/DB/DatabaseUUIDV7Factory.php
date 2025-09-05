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

namespace Tuleap\DB;

use Ramsey\Uuid\Rfc4122\UuidV7;
use Tuleap\Option\Option;

final class DatabaseUUIDV7Factory implements DatabaseUUIDFactory
{
    #[\Override]
    public function buildUUIDBytes(): string
    {
        return $this->buildUUIDBytesFromTime(new \DateTimeImmutable());
    }

    #[\Override]
    public function buildUUIDBytesFromTime(\DateTimeInterface $time): string
    {
        return UuidV7::uuid7($time)->getBytes();
    }

    #[\Override]
    public function buildUUIDFromBytesData(string $bytes): UUID
    {
        return new UUIDFromRamseyUUIDLibrary(UuidV7::fromBytes($bytes));
    }

    #[\Override]
    public function buildUUIDFromHexadecimalString(string $string): Option
    {
        try {
            /** @psalm-var UUID $uuid */
            $uuid = new UUIDFromRamseyUUIDLibrary(UuidV7::fromString($string));
        } catch (\Ramsey\Uuid\Exception\InvalidUuidStringException $exception) {
            return Option::nothing(UUID::class);
        }

        return Option::fromValue($uuid);
    }
}
