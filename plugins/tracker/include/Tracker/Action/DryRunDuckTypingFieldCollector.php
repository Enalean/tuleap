<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;

final class DryRunDuckTypingFieldCollector implements CollectDryRunTypingField
{
    public function __construct(
        private readonly RetrieveUsedFields $retrieve_source_tracker_used_fields,
        private readonly RetrieveUsedFields $retrieve_target_tracker_used_fields,
        private readonly CheckFieldTypeCompatibility $check_field_type_compatibility,
    ) {
    }

    public function collect(\Tracker $source_tracker, \Tracker $target_tracker): DuckTypedMoveFieldCollection
    {
        $migrateable_fields    = [];
        $not_migrateable_field = [];
        $mapping               = [];

        foreach ($this->retrieve_source_tracker_used_fields->getUsedFields($source_tracker) as $source_field) {
            $target_field = $this->retrieve_target_tracker_used_fields->getUsedFieldByName($target_tracker->getId(), $source_field->getName());
            if ($target_field === null) {
                $not_migrateable_field[] = $source_field;
                continue;
            }

            if (! $this->check_field_type_compatibility->areTypesCompatible($target_field, $source_field)) {
                $not_migrateable_field[] = $source_field;
                continue;
            }

            $mapping[]            = FieldMapping::fromFields($source_field, $target_field);
            $migrateable_fields[] = $source_field;
        }

        return DuckTypedMoveFieldCollection::fromFields($migrateable_fields, $not_migrateable_field, $mapping);
    }
}
