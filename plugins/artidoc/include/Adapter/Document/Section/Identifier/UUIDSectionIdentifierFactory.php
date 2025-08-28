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

namespace Tuleap\Artidoc\Adapter\Document\Section\Identifier;

use Override;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\InvalidSectionIdentifierStringException;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\DB\DatabaseUUIDFactory;

final readonly class UUIDSectionIdentifierFactory implements SectionIdentifierFactory
{
    public function __construct(private DatabaseUUIDFactory $uuid_factory)
    {
    }

    #[Override]
    public function buildIdentifier(): SectionIdentifier
    {
        return UUIDSectionIdentifier::fromUUID(
            $this->uuid_factory->buildUUIDFromBytesData($this->uuid_factory->buildUUIDBytes())
        );
    }

    #[Override]
    public function buildFromBytesData(string $bytes): SectionIdentifier
    {
        return UUIDSectionIdentifier::fromUUID($this->uuid_factory->buildUUIDFromBytesData($bytes));
    }

    /**
     * @throws InvalidSectionIdentifierStringException
     */
    #[Override]
    public function buildFromHexadecimalString(string $string): SectionIdentifier
    {
        return $this->uuid_factory->buildUUIDFromHexadecimalString($string)
            ->match(
                UUIDSectionIdentifier::fromUUID(...),
                static fn () => throw new InvalidSectionIdentifierStringException($string)
            );
    }
}
