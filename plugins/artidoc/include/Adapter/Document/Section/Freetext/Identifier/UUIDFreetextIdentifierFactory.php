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

namespace Tuleap\Artidoc\Adapter\Document\Section\Freetext\Identifier;

use Override;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\Identifier\FreetextIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\Identifier\FreetextIdentifierFactory;
use Tuleap\DB\DatabaseUUIDFactory;

final readonly class UUIDFreetextIdentifierFactory implements FreetextIdentifierFactory
{
    public function __construct(private DatabaseUUIDFactory $uuid_factory)
    {
    }

    #[Override]
    public function buildIdentifier(): FreetextIdentifier
    {
        return UUIDFreetextIdentifier::fromUUID(
            $this->uuid_factory->buildUUIDFromBytesData($this->uuid_factory->buildUUIDBytes())
        );
    }

    #[Override]
    public function buildFromBytesData(string $bytes): FreetextIdentifier
    {
        return UUIDFreetextIdentifier::fromUUID($this->uuid_factory->buildUUIDFromBytesData($bytes));
    }
}
