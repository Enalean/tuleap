<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Duplication;

final readonly class DuplicationUserGroupMapping
{
    /**
     * @param array<int, int> $ugroup_mapping
     */
    private function __construct(public DuplicationType $duplication_type, public array $ugroup_mapping)
    {
    }

    public static function fromTypeAndMapping(DuplicationType $duplicate_type, array $ugroup_mapping): self
    {
        return new self($duplicate_type, $ugroup_mapping);
    }

    public static function fromAnotherProjectWithoutMapping(): self
    {
        return new self(DuplicationType::DUPLICATE_OTHER_PROJECT, []);
    }

    public static function fromSameProjectWithoutMapping(): self
    {
        return new self(DuplicationType::DUPLICATE_SAME_PROJECT, []);
    }

    /**
     * @param array<int, int> $mapping
     */
    public static function fromSameProjectWithMapping(array $mapping): self
    {
        return new self(DuplicationType::DUPLICATE_SAME_PROJECT, $mapping);
    }

    /**
     * @param array<int, int> $mapping
     */
    public static function fromNewProjectWithMapping(array $mapping): self
    {
        return new self(DuplicationType::DUPLICATE_NEW_PROJECT, $mapping);
    }
}
