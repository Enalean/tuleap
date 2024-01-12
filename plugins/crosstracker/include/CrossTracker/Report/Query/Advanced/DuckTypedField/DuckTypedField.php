<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

/**
 * @psalm-immutable
 */
final class DuckTypedField
{
    private function __construct(public readonly DuckTypedFieldType $type)
    {
    }

    /**
     * @param int[] $tracker_ids
     * @param list<Ok<DuckTypedFieldType> | Err<Fault>> $types
     * @return Ok<self>|Err<Fault>
     */
    public static function build(string $field_name, array $tracker_ids, array $types): Ok|Err
    {
        if (count($types) === 0) {
            return Result::err(FieldNotFoundInAnyTrackerFault::build());
        }
        $other_results = array_slice($types, 1);

        return $types[0]->andThen(static function (DuckTypedFieldType $first_type) use ($field_name, $tracker_ids, $other_results) {
            foreach ($other_results as $other_result) {
                if ($other_result->unwrapOr(null) !== $first_type) {
                    return Result::err(FieldTypesAreIncompatibleFault::build($field_name, $tracker_ids));
                }
            }
            return Result::ok(new self($first_type));
        });
    }
}
