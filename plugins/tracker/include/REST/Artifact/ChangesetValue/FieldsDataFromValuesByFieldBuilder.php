<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact\ChangesetValue;

use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValuesContainer;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;

final class FieldsDataFromValuesByFieldBuilder
{
    public function __construct(
        private readonly RetrieveUsedFields $fields_retriever,
        private readonly NewArtifactLinkInitialChangesetValueBuilder $artifact_link_initial_builder,
    ) {
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldException
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    public function getFieldsDataOnCreate(array $values, \Tracker $tracker): InitialChangesetValuesContainer
    {
        $new_values    = [];
        $artifact_link = Option::nothing(NewArtifactLinkInitialChangesetValue::class);
        foreach ($values as $field_name => $value) {
            $field = $this->getFieldByName($tracker, $field_name);
            if ($field instanceof \Tracker_FormElement_Field_ArtifactLink) {
                $artifact_link = Option::fromValue(
                    $this->artifact_link_initial_builder->buildFromPayload($field, $value)
                );
                continue;
            }

            $new_values[$field->getId()] = $field->getFieldDataFromRESTValueByfield($value);
        }

        return new InitialChangesetValuesContainer($new_values, $artifact_link);
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldException
     */
    private function getFieldByName(\Tracker $tracker, string $field_name): \Tracker_FormElement_Field
    {
        $field = $this->fields_retriever->getUsedFieldByName($tracker->getId(), $field_name);
        if (! $field) {
            throw new \Tracker_FormElement_InvalidFieldException("Field $field_name does not exist in the tracker");
        }

        return $field;
    }
}
