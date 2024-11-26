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

namespace Tuleap\Artidoc\Document;

use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;

final readonly class RawSection
{
    public function __construct(
        public SectionIdentifier $id,
        public int $item_id,
        public int $artifact_id,
        public int $rank,
    ) {
    }

    /**
     * @param array{ id: SectionIdentifier, item_id: int, artifact_id: int, rank: int } $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['item_id'],
            $row['artifact_id'],
            $row['rank'],
        );
    }
}
