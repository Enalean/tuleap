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

use Override;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Date;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_Artifact_ChangesetValue_Numeric;
use Tracker_Artifact_ChangesetValue_PermissionsOnArtifact;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\Artidoc\Document\Field\ArtifactLink\ArtifactLinkFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\Date\DateFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\List\ListFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\Numeric\NumericFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\Permissions\PermissionsOnArtifactFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\StepsDefinition\StepsDefinitionFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\StepsExecution\StepsExecutionFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\User\UserFieldWithValueBuilder;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\FieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\TextFieldWithValue;
use Tuleap\TestManagement\Step\Definition\Field\StepsDefinition;
use Tuleap\TestManagement\Step\Definition\Field\StepsDefinitionChangesetValue;
use Tuleap\TestManagement\Step\Execution\Field\StepsExecution;
use Tuleap\TestManagement\Step\Execution\Field\StepsExecutionChangesetValue;
use Tuleap\Tracker\Artifact\Changeset\ArtifactLink\ArtifactLinkChangesetValue;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\Tracker\FormElement\Field\LastUpdateBy\LastUpdateByField;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\FormElement\Field\NumericField;
use Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionsOnArtifactField;
use Tuleap\Tracker\FormElement\Field\SubmittedBy\SubmittedByField;
use Tuleap\Tracker\FormElement\Field\Text\TextField;

final readonly class FieldsWithValuesBuilder implements GetFieldsWithValues
{
    public function __construct(
        private ConfiguredFieldCollection $field_collection,
        private ListFieldWithValueBuilder $list_field_with_value_builder,
        private ArtifactLinkFieldWithValueBuilder $artifact_link_field_with_value_builder,
        private NumericFieldWithValueBuilder $numeric_field_with_value_builder,
        private UserFieldWithValueBuilder $user_field_with_value_builder,
        private DateFieldWithValueBuilder $date_field_with_value_builder,
        private PermissionsOnArtifactFieldWithValueBuilder $permissions_on_artifact_field_with_value_builder,
        private StepsDefinitionFieldWithValueBuilder $step_definition_field_with_value_builder,
        private StepsExecutionFieldWithValueBuilder $steps_execution_field_with_value_builder,
    ) {
    }

    #[Override]
    public function getFieldsWithValues(Tracker_Artifact_Changeset $changeset): array
    {
        return array_reduce(
            $this->field_collection->getFields($changeset->getTracker()),
            /**
             * @param list<FieldWithValue> $fields
             */
            fn(array $fields, ConfiguredField $configured_field) => $this->buildFieldWithValue($fields, $changeset, $configured_field),
            [],
        );
    }

    /**
     * @param list<FieldWithValue> $fields
     * @return list<FieldWithValue>
     */
    private function buildFieldWithValue(
        array $fields,
        Tracker_Artifact_Changeset $changeset,
        ConfiguredField $configured_field,
    ): array {
        $changeset_value = $changeset->getValue($configured_field->field);

        if ($configured_field->field instanceof TextField) {
            assert($changeset_value === null || $changeset_value instanceof Tracker_Artifact_ChangesetValue_Text);
            return [...$fields,
                new TextFieldWithValue(
                    $configured_field->field->getLabel(),
                    $configured_field->display_type,
                    $changeset_value?->getValue() ?? '',
                ),
            ];
        }

        if (
            $configured_field->field instanceof LastUpdateByField
            || $configured_field->field instanceof SubmittedByField
        ) {
            return [...$fields, $this->user_field_with_value_builder->buildUserFieldWithValue($configured_field, $changeset)];
        }

        if ($configured_field->field instanceof ListField) {
            assert($changeset_value === null || $changeset_value instanceof Tracker_Artifact_ChangesetValue_List);
            return [...$fields, $this->list_field_with_value_builder->buildListFieldWithValue($configured_field, $changeset_value)];
        }

        if ($configured_field->field instanceof ArtifactLinkField) {
            assert($changeset_value === null || $changeset_value instanceof ArtifactLinkChangesetValue);
            return [...$fields, $this->artifact_link_field_with_value_builder->buildArtifactLinkFieldWithValue($configured_field, $changeset_value)];
        }

        if ($configured_field->field instanceof NumericField) {
            assert($changeset_value === null || $changeset_value instanceof Tracker_Artifact_ChangesetValue_Numeric);
            return [...$fields, $this->numeric_field_with_value_builder->buildNumericFieldWithValue($configured_field, $changeset->getArtifact(), $changeset_value)];
        }

        if ($configured_field->field instanceof DateField) {
            assert($changeset_value === null || $changeset_value instanceof Tracker_Artifact_ChangesetValue_Date);
            return [...$fields, $this->date_field_with_value_builder->buildDateFieldWithValue($configured_field, $changeset, $changeset_value)];
        }

        if ($configured_field->field instanceof PermissionsOnArtifactField) {
            assert($changeset_value === null || $changeset_value instanceof Tracker_Artifact_ChangesetValue_PermissionsOnArtifact);
            return [...$fields, $this->permissions_on_artifact_field_with_value_builder->buildPermissionsOnArtifactFieldWithValue($configured_field, $changeset_value)];
        }

        if ($configured_field->field instanceof StepsDefinition) {
            assert($changeset_value === null || $changeset_value instanceof StepsDefinitionChangesetValue);
            return [...$fields, $this->step_definition_field_with_value_builder->buildStepsDefinitionFieldWithValue($configured_field, $changeset_value)];
        }

        if ($configured_field->field instanceof StepsExecution) {
            assert($changeset_value === null || $changeset_value instanceof StepsExecutionChangesetValue);
            return [...$fields, $this->steps_execution_field_with_value_builder->buildStepsExecutionFieldWithValue($configured_field, $changeset_value)];
        }

        return $fields;
    }
}
