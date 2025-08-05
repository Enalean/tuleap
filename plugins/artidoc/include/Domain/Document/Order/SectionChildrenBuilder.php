<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Domain\Document\Order;

use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\EmptyDocumentFault;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\SearchAllSections;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class SectionChildrenBuilder
{
    public function __construct(
        private SearchAllSections $search_all_sections,
    ) {
    }

    /**
     * @return Ok<list<SectionIdentifier>>|Err<Fault>
     */
    public function getSectionChildren(SectionIdentifier $section_identifier, ArtidocWithContext $artidoc): Ok|Err
    {
        $artidoc_sections = $this->search_all_sections->searchAllSectionsOfDocument($artidoc);

        if ($artidoc_sections === []) {
            return Result::err(EmptyDocumentFault::build());
        }

        $index_of_current_section = 0;
        foreach ($artidoc_sections as $index => $artidoc_section) {
            if ($artidoc_section->id->toString() === $section_identifier->toString()) {
                $index_of_current_section = $index;
                break;
            }
        }

        $level    = $artidoc_sections[$index_of_current_section]->level;
        $children = [];

        for ($i = 1; $i + $index_of_current_section <= count($artidoc_sections) - 1; $i++) {
            $next_section = $artidoc_sections[$index_of_current_section + $i];
            if ($next_section->level->value <= $level->value) {
                break;
            }
            $children[] = $next_section->id;
        }
        return Result::ok($children);
    }
}
