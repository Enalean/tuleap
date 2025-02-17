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

namespace Tuleap\Artidoc\Domain\Document\Section;

use Tuleap\Artidoc\Domain\Document\Section\Freetext\Identifier\FreetextIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;

final readonly class RetrievedSection
{
    private function __construct(
        public SectionIdentifier $id,
        public Level $level,
        public int $item_id,
        public RetrievedSectionContent $content,
        public int $rank,
    ) {
    }

    /**
     * @param array{ id: SectionIdentifier, level: int, item_id: int, artifact_id: int, rank: int, ... } $row
     */
    public static function fromArtifact(array $row): self
    {
        return new self(
            $row['id'],
            Level::from($row['level']),
            $row['item_id'],
            RetrievedSectionContent::fromArtifact($row['artifact_id']),
            $row['rank'],
        );
    }

    /**
     * @param array{ id: SectionIdentifier, level: int, item_id: int, freetext_id: FreetextIdentifier, freetext_title: string, freetext_description: string, rank: int, ... } $row
     */
    public static function fromFreetext(array $row): self
    {
        $level = Level::from($row['level']);

        return new self(
            $row['id'],
            $level,
            $row['item_id'],
            RetrievedSectionContent::fromFreetext($row['freetext_id'], $row['freetext_title'], $row['freetext_description'], $level),
            $row['rank'],
        );
    }
}
