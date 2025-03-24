<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1;

use Tracker_Artifact_Changeset;
use Tuleap\Artidoc\Document\Field\ConfiguredFieldCollection;

final readonly class SectionFieldsBuilder implements BuildSectionFields
{
    public function __construct(private ConfiguredFieldCollection $field_collection)
    {
    }

    /**
     * @return list<SectionStringFieldRepresentation>
     */
    public function getFields(
        Tracker_Artifact_Changeset $changeset,
    ): array {
        $fields = [];
        foreach ($this->field_collection->getFields($changeset->getTracker()) as $configured_field) {
            $changeset_value = $changeset->getValue($configured_field->field);
            if (! $changeset_value instanceof \Tracker_Artifact_ChangesetValue_String) {
                continue;
            }

            $fields[] = new SectionStringFieldRepresentation(
                $configured_field,
                $changeset_value->getValue(),
            );
        }

        return $fields;
    }
}
