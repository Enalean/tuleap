<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Tests\Stub;

use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\DuckTypedFieldType;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\SearchFieldTypes;

final class SearchFieldTypesStub implements SearchFieldTypes
{
    private function __construct(private readonly array $types)
    {
    }

    public static function withTypes(string $first_type, string ...$other_types): self
    {
        return new self([$first_type, ...$other_types]);
    }

    public static function withNoTypeFound(): self
    {
        return new self([]);
    }

    public function searchTypeByFieldNameAndTrackerList(string $field_name, array $tracker_ids): array
    {
        $types = [];
        foreach ($this->types as $type_names) {
            $types[] = DuckTypedFieldType::fromString($type_names);
        }
        return $types;
    }
}
