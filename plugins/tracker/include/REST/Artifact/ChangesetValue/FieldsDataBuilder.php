<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use PFUser;
use Tracker;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_ReadOnly;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValuesContainer;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValuesContainer;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\Permission\FieldPermissionType;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnFields;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

final readonly class FieldsDataBuilder
{
    public function __construct(
        private RetrieveUsedFields $fields_retriever,
        private NewArtifactLinkChangesetValueBuilder $artifact_link_builder,
        private NewArtifactLinkInitialChangesetValueBuilder $artifact_link_initial_builder,
        private RetrieveUserPermissionOnFields $user_permission_on_fields,
    ) {
    }

    /**
     * @param array<ArtifactValuesRepresentation> $values
     *
     * @throws \Tracker_FormElement_InvalidFieldException
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    public function getFieldsDataOnCreate(array $values, Tracker $tracker, PFUser $user): InitialChangesetValuesContainer
    {
        $new_values     = [];
        $artifact_link  = Option::nothing(NewArtifactLinkInitialChangesetValue::class);
        $indexed_fields = $this->getIndexedFields($tracker);
        foreach ($values as $value) {
            $array_representation = $value->toArray();

            $field = $this->getField($indexed_fields, $array_representation);
            if ($field instanceof ArtifactLinkField) {
                $artifact_link = Option::fromValue(
                    $this->artifact_link_initial_builder->buildFromPayload($field, $value)
                );
                continue;
            }
            $new_values[$field->getId()] = $field->getFieldDataFromRESTValue($array_representation);
        }

        // Fill empty values
        $fields_user_can_submit = $this->user_permission_on_fields
            ->retrieveUserPermissionOnFields($user, $indexed_fields, FieldPermissionType::PERMISSION_SUBMIT)
            ->allowed;
        foreach ($fields_user_can_submit as $field) {
            if (isset($new_values[$field->getId()])) {
                continue;
            }
            if ($field instanceof Tracker_FormElement_Field_ReadOnly) {
                continue;
            }

            if ($field instanceof ArtifactLinkField) {
                $artifact_link = $artifact_link->orElse(function () use ($field) {
                    $empty_payload            = new ArtifactValuesRepresentation();
                    $empty_payload->all_links = [];
                    return Option::fromValue(
                        $this->artifact_link_initial_builder->buildFromPayload($field, $empty_payload)
                    );
                });
                continue;
            }
            $new_values[$field->getId()] = $field->getValueFromSubmitOrDefault([]);
        }

        return new InitialChangesetValuesContainer($new_values, $artifact_link);
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldException
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    public function getFieldsDataOnUpdate(
        array $values,
        Artifact $artifact,
        PFUser $submitter,
    ): ChangesetValuesContainer {
        $new_values     = [];
        $artifact_link  = Option::nothing(NewArtifactLinkChangesetValue::class);
        $indexed_fields = $this->getIndexedFields($artifact->getTracker());
        foreach ($values as $value) {
            $array_representation = $value->toArray();

            $field = $this->getField($indexed_fields, $array_representation);
            if ($field instanceof ArtifactLinkField) {
                $artifact_link = Option::fromValue(
                    $this->artifact_link_builder->buildFromPayload(
                        $artifact,
                        $field,
                        $submitter,
                        $value
                    )
                );
                continue;
            }
            $new_values[$field->getId()] = $field->getFieldDataFromRESTValue($array_representation, $artifact);
        }
        return new ChangesetValuesContainer($new_values, $artifact_link);
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldException
     */
    private function getField(array $indexed_fields, array $value): Tracker_FormElement_Field
    {
        if (! isset($value['field_id']) || (isset($value['field_id']) && ! is_int($value['field_id']))) {
            throw new \Tracker_FormElement_InvalidFieldException(
                'No \'field_id\' or invalid id in submitted value. Field IDs must be integers'
            );
        }
        if (! isset($indexed_fields[$value['field_id']])) {
            throw new \Tracker_FormElement_InvalidFieldException('Unknown field ' . $value['field_id']);
        }
        return $indexed_fields[$value['field_id']];
    }

    /**
     * @return array<int, Tracker_FormElement_Field>
     */
    private function getIndexedFields(Tracker $tracker): array
    {
        $indexed_fields = [];
        foreach ($this->fields_retriever->getUsedFields($tracker) as $field) {
            $indexed_fields[$field->getId()] = $field;
        }
        return $indexed_fields;
    }
}
