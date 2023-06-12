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

/**
 * @psalm-immutable
 */
final class DuckTypedMoveFieldCollection
{
    /**
     * @param \Tracker_FormElement_Field[] $migrateable_field_list
     * @param \Tracker_FormElement_Field[] $not_migrateable_field_list
     * @param \Tracker_FormElement_Field[] $partially_migrated_fields
     * @param FieldMapping[] $mapping_fields
     */
    private function __construct(public array $migrateable_field_list, public array $not_migrateable_field_list, public array $partially_migrated_fields, public array $mapping_fields)
    {
    }

    /**
     * @param \Tracker_FormElement_Field[] $migrateable_field_list
     * @param \Tracker_FormElement_Field[] $not_migrateable_field_list
     * @param FieldMapping[] $mapping_fields
     */
    public static function fromFields(array $migrateable_field_list, array $not_migrateable_field_list, array $partially_migrated_fields, array $mapping_fields): self
    {
        return new self($migrateable_field_list, $not_migrateable_field_list, $partially_migrated_fields, $mapping_fields);
    }
}
