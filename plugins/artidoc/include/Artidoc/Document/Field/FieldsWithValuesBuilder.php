<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Field;

use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StringFieldWithValue;

final readonly class FieldsWithValuesBuilder implements GetFieldsWithValues
{
    public function __construct(private ConfiguredFieldCollection $field_collection)
    {
    }

    public function getFieldsWithValues(\Tracker_Artifact_Changeset $changeset): array
    {
        $fields  = [];
        $tracker = $changeset->getTracker();
        foreach ($this->field_collection->getFields($tracker) as $configured_field) {
            $changeset_value = $changeset->getValue($configured_field->field);
            if ($changeset_value instanceof \Tracker_Artifact_ChangesetValue_String) {
                $fields[] = new StringFieldWithValue(
                    $configured_field->field->getLabel(),
                    $configured_field->display_type,
                    $changeset_value->getValue()
                );
            }
        }
        return $fields;
    }
}
