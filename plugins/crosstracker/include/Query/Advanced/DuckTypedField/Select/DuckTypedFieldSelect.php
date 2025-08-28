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

namespace Tuleap\CrossTracker\Query\Advanced\DuckTypedField\Select;

use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\FieldTypeRetrieverWrapper;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\FieldTypesAreIncompatibleFault;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\FormElement\RetrieveFieldType;

/**
 * @psalm-immutable
 */
final readonly class DuckTypedFieldSelect
{
    /**
     * @param list<int> $field_ids
     */
    private function __construct(
        public string $name,
        public array $field_ids,
        public DuckTypedFieldTypeSelect $type,
    ) {
    }

    /**
     * @param TrackerField[] $fields
     * @param int[] $tracker_ids
     * @return Ok<self>|Err<Fault>
     */
    public static function build(
        RetrieveFieldType $retrieve_field_type,
        string $field_name,
        array $fields,
        array $tracker_ids,
    ): Ok|Err {
        if (count($fields) === 0) {
            return DuckTypedFieldTypeSelect::fromString(FieldTypeRetrieverWrapper::UNKNOWN_FIELD_TYPE)
                ->map(fn (DuckTypedFieldTypeSelect $type) => new self($field_name, [], $type));
        }

        $field_identifiers = [];
        foreach ($fields as $field) {
            $field_identifiers[] = DuckTypedFieldTypeSelect::fromString($retrieve_field_type->getType($field))
                ->map(static fn(DuckTypedFieldTypeSelect $type) => new FieldIdentifierPropertiesSelect($field->getId(), $type));
        }

        $other_results = array_slice($field_identifiers, 1);

        return $field_identifiers[0]->andThen(
            static function (FieldIdentifierPropertiesSelect $first_field) use ($field_name, $tracker_ids, $other_results) {
                $field_ids = [$first_field->id];
                foreach ($other_results as $other_result) {
                    if (Result::isErr($other_result)) {
                        return Result::err(FieldTypesAreIncompatibleFault::build($field_name, $tracker_ids));
                    }
                    if ($first_field->type !== $other_result->value->type) {
                        return Result::err(FieldTypesAreIncompatibleFault::build($field_name, $tracker_ids));
                    }
                    $field_ids[] = $other_result->value->id;
                }
                return Result::ok(new self($field_name, $field_ids, $first_field->type));
            }
        );
    }
}
