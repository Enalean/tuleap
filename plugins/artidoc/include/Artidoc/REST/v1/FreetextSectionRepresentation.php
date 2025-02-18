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

namespace Tuleap\Artidoc\REST\v1;

use Tuleap\Artidoc\Domain\Document\Section\Freetext\RetrievedSectionContentFreetext;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Level;

/**
 * @psalm-immutable
 */
final readonly class FreetextSectionRepresentation implements SectionRepresentation
{
    public string $type;

    private function __construct(
        public string $id,
        public int $level,
        public string $title,
        public string $description,
    ) {
        $this->type = 'freetext';
    }

    public static function fromRetrievedSectionContentFreetext(
        SectionIdentifier $section_identifier,
        Level $level,
        RetrievedSectionContentFreetext $freetext,
    ): self {
        return new self(
            $section_identifier->toString(),
            $level->value,
            $freetext->content->title,
            $freetext->content->description,
        );
    }
}
