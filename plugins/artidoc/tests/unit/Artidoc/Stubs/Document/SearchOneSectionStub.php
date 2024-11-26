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

namespace Tuleap\Artidoc\Stubs\Document;

use Tuleap\Artidoc\Document\RawSection;
use Tuleap\Artidoc\Document\SearchOneSection;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;

final class SearchOneSectionStub implements SearchOneSection
{
    private function __construct(private ?RawSection $row)
    {
    }

    public static function withResults(RawSection $row): self
    {
        return new self($row);
    }

    public static function withoutResults(): self
    {
        return new self(null);
    }

    public function searchSectionById(SectionIdentifier $section_id): ?RawSection
    {
        return $this->row;
    }
}
