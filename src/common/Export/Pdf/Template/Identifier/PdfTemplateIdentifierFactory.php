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

namespace Tuleap\Export\Pdf\Template\Identifier;

use Tuleap\DB\DatabaseUUIDFactory;

final class PdfTemplateIdentifierFactory
{
    public function __construct(private DatabaseUUIDFactory $uuid_factory)
    {
    }

    public function buildIdentifier(): PdfTemplateIdentifier
    {
        return PdfTemplateIdentifier::fromUUID(
            $this->uuid_factory->buildUUIDFromBytesData($this->uuid_factory->buildUUIDBytes())
        );
    }

    public function buildFromBytesData(string $bytes): PdfTemplateIdentifier
    {
        return PdfTemplateIdentifier::fromUUID($this->uuid_factory->buildUUIDFromBytesData($bytes));
    }

    /**
     * @throws InvalidPdfTemplateIdentifierStringException
     */
    public function buildFromHexadecimalString(string $string): PdfTemplateIdentifier
    {
        return $this->uuid_factory->buildUUIDFromHexadecimalString($string)
            ->match(
                PdfTemplateIdentifier::fromUUID(...),
                static fn () => throw new InvalidPdfTemplateIdentifierStringException($string)
            );
    }
}
