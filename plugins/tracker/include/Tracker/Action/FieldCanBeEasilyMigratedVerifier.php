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

use Tuleap\Tracker\FormElement\RetrieveFieldType;

final class FieldCanBeEasilyMigratedVerifier implements VerifyFieldCanBeEasilyMigrated
{
    private const STRING_TYPES_COMPATIBILITIES = [\Tracker_FormElementFactory::FIELD_STRING_TYPE, \Tracker_FormElementFactory::FIELD_TEXT_TYPE];
    private const NUMBER_TYPES_COMPATIBILITIES = [\Tracker_FormElementFactory::FIELD_FLOAT_TYPE, \Tracker_FormElementFactory::FIELD_INTEGER_TYPE];
    private const EASILY_MOVABLE_FIELDS        = [
        \Tracker_FormElementFactory::FIELD_DATE_TYPE,
        \Tracker_FormElementFactory::FIELD_SUBMITTED_ON_TYPE,
        \Tracker_FormElementFactory::FIELD_LAST_UPDATE_DATE_TYPE,
        \Tracker_FormElementFactory::FIELD_ARTIFACT_ID_TYPE,
        \Tracker_FormElementFactory::FIELD_CROSS_REFERENCES,
        \Tracker_FormElementFactory::FIELD_LAST_MODIFIED_BY,
        \Tracker_FormElementFactory::FIELD_SUBMITTED_BY_TYPE,
        \Tracker_FormElementFactory::FIELD_COMPUTED,
        \Tracker_FormElementFactory::FIELD_ARTIFACT_IN_TRACKER,
        \Tracker_FormElementFactory::FIELD_RANK,
        \Tracker_FormElementFactory::FIELD_BURNDOWN,
        \Tracker_FormElementFactory::FIELD_SHARED,
        \Tracker_FormElementFactory::FIELD_FILE_TYPE,
        \Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS,
    ];

    public function __construct(
        private readonly RetrieveFieldType $retrieve_source_field_type,
        private readonly RetrieveFieldType $retrieve_destination_field_type,
    ) {
    }

    public function canFieldBeEasilyMigrated(
        \Tracker_FormElement_Field $source_field,
        \Tracker_FormElement_Field $destination_field,
    ): bool {
        $destination_field_type = $this->retrieve_source_field_type->getType($destination_field);
        $source_field_type      = $this->retrieve_destination_field_type->getType($source_field);

        return $this->areTypesCompatible($source_field_type, $destination_field_type) ||
            $this->isAnEasilyMovableField($destination_field_type, $source_field_type);
    }

    private function isAnEasilyMovableField(
        string $destination_field_type,
        string $source_field_type,
    ): bool {
        return $source_field_type === $destination_field_type && in_array($source_field_type, self::EASILY_MOVABLE_FIELDS, true);
    }

    private function areTypesCompatible(string $source_field_type, string $destination_field_type): bool
    {
        return (
                in_array($source_field_type, self::STRING_TYPES_COMPATIBILITIES, true) &&
                in_array($destination_field_type, self::STRING_TYPES_COMPATIBILITIES, true)
            ) || (
                in_array($source_field_type, self::NUMBER_TYPES_COMPATIBILITIES, true) &&
                in_array($destination_field_type, self::NUMBER_TYPES_COMPATIBILITIES, true)
            );
    }
}
