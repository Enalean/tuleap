<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Tus\Identifier;

use Tuleap\DB\DatabaseUUIDFactory;

final readonly class UUIDFileIdentifierFactory implements FileIdentifierFactory
{
    public function __construct(private DatabaseUUIDFactory $uuid_factory)
    {
    }

    #[\Override]
    public function buildIdentifier(): FileIdentifier
    {
        return UUIDFileIdentifier::fromUUID(
            $this->uuid_factory->buildUUIDFromBytesData($this->uuid_factory->buildUUIDBytes())
        );
    }

    #[\Override]
    public function buildFromBytesData(string $bytes): FileIdentifier
    {
        return UUIDFileIdentifier::fromUUID($this->uuid_factory->buildUUIDFromBytesData($bytes));
    }

    /**
     * @throws InvalidFileIdentifierStringException
     */
    #[\Override]
    public function buildFromHexadecimalString(string $string): FileIdentifier
    {
        return $this->uuid_factory->buildUUIDFromHexadecimalString($string)
            ->match(
                UUIDFileIdentifier::fromUUID(...),
                static fn () => throw new InvalidFileIdentifierStringException($string)
            );
    }
}
