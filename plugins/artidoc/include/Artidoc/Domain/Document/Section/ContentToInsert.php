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

use Tuleap\Artidoc\Domain\Document\Section\Freetext\FreetextContent;
use Tuleap\Option\Option;

/**
 * @psalm-immutable
 */
final readonly class ContentToInsert
{
    /**
     * @param Option<int> $artifact_id
     * @param Option<FreetextContent> $freetext
     */
    private function __construct(
        public Option $artifact_id,
        public Option $freetext,
        public Level $level,
    ) {
    }

    public static function fromArtifactId(int $artifact_id, Level $level): self
    {
        return new self(
            Option::fromValue($artifact_id),
            Option::nothing(FreetextContent::class),
            $level,
        );
    }

    public static function fromFreetext(FreetextContent $freetext): self
    {
        return new self(
            Option::nothing(\Psl\Type\int()),
            Option::fromValue($freetext),
            $freetext->level,
        );
    }
}
