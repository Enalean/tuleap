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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;

final class DryRunDuckTypingFieldCollector implements CollectDryRunTypingField
{
    /**
     * @var \Tracker_FormElement_Field[]
     */
    private array $migrateable_fields = [];
    /**
     * @var \Tracker_FormElement_Field[]
     */
    private array $not_migrateable_fields = [];
    /**
     * @var \Tracker_FormElement_Field[]
     */
    private array $partially_migrated_fields = [];
    /**
     * @var FieldMapping[]
     */
    private array $fields_mapping = [];

    public function __construct(
        private readonly RetrieveUsedFields $retrieve_source_tracker_used_fields,
        private readonly RetrieveUsedFields $retrieve_target_tracker_used_fields,
        private readonly VerifyFieldCanBeEasilyMigrated $verify_field_can_be_easily_migrated,
        private readonly VerifyIsStaticListField $verify_is_static_list_field,
        private readonly VerifyStaticListFieldsAreCompatible $verify_static_fields_are_compatible,
        private readonly VerifyStaticFieldValuesCanBeFullyMoved $verify_static_field_values_can_be_fully_moved,
        private readonly VerifyIsUserListField $verify_is_user_list_field,
        private readonly VerifyUserFieldsAreCompatible $verify_user_fields_are_compatible,
        private readonly VerifyUserFieldValuesCanBeFullyMoved $verify_user_field_values_can_be_fully_moved,
    ) {
    }

    public function collect(\Tracker $source_tracker, \Tracker $target_tracker, Artifact $artifact): DuckTypedMoveFieldCollection
    {
        foreach ($this->retrieve_source_tracker_used_fields->getUsedFields($source_tracker) as $source_field) {
            $target_field = $this->retrieve_target_tracker_used_fields->getUsedFieldByName($target_tracker->getId(), $source_field->getName());
            if ($target_field === null) {
                $this->addFieldToNotMigrateableList($source_field);
                continue;
            }

            if ($this->verify_field_can_be_easily_migrated->canFieldBeEasilyMigrated($target_field, $source_field)) {
                $this->addFieldToMigrateableList($source_field, $target_field);

                continue;
            }

            if (
                $this->verify_is_static_list_field->isStaticListField($source_field)
                && $this->verify_is_static_list_field->isStaticListField($target_field)
            ) {
                assert($source_field instanceof \Tracker_FormElement_Field_List);
                assert($target_field instanceof \Tracker_FormElement_Field_List);
                $this->collectStaticFields($source_field, $target_field, $artifact);
                continue;
            }

            if (
                $this->verify_is_user_list_field->isUserListField($source_field)
                && $this->verify_is_user_list_field->isUserListField($target_field)
            ) {
                assert($source_field instanceof \Tracker_FormElement_Field_List);
                assert($target_field instanceof \Tracker_FormElement_Field_List);
                $this->collectUserBoundedFields($source_field, $target_field, $artifact);
                continue;
            }

            $this->addFieldToNotMigrateableList($source_field);
        }

        return DuckTypedMoveFieldCollection::fromFields($this->migrateable_fields, $this->not_migrateable_fields, $this->partially_migrated_fields, $this->fields_mapping);
    }

    private function collectStaticFields(
        \Tracker_FormElement_Field_List $source_field,
        \Tracker_FormElement_Field_List $target_field,
        Artifact $artifact,
    ): void {
        if (! $this->verify_static_fields_are_compatible->areStaticFieldsCompatible($source_field, $target_field, $artifact)) {
            $this->addFieldToNotMigrateableList($source_field);
            return;
        }

        if (! $this->verify_static_field_values_can_be_fully_moved->canAllStaticFieldValuesBeMoved($source_field, $target_field, $artifact)) {
            $this->addFieldToPartiallyMigratedList($source_field, $target_field);
            return;
        }

        $this->addFieldToMigrateableList($source_field, $target_field);
    }

    private function collectUserBoundedFields(
        \Tracker_FormElement_Field_List $source_field,
        \Tracker_FormElement_Field_List $target_field,
        Artifact $artifact,
    ): void {
        if (! $this->verify_user_fields_are_compatible->areUserFieldsCompatible($source_field, $target_field, $artifact)) {
            $this->addFieldToNotMigrateableList($source_field);
            return;
        }

        if (! $this->verify_user_field_values_can_be_fully_moved->canAllUserFieldValuesBeMoved($source_field, $target_field, $artifact)) {
            $this->addFieldToPartiallyMigratedList($source_field, $target_field);
            return;
        }

        $this->addFieldToMigrateableList($source_field, $target_field);
    }

    private function addFieldToMigrateableList(\Tracker_FormElement_Field $source_field, \Tracker_FormElement_Field $target_field): void
    {
        $this->fields_mapping[]     = FieldMapping::fromFields($source_field, $target_field);
        $this->migrateable_fields[] = $source_field;
    }

    private function addFieldToPartiallyMigratedList(\Tracker_FormElement_Field $source_field, \Tracker_FormElement_Field $target_field): void
    {
        $this->partially_migrated_fields[] = $source_field;
        $this->fields_mapping[]            = FieldMapping::fromFields($source_field, $target_field);
    }

    private function addFieldToNotMigrateableList(\Tracker_FormElement_Field $source_field): void
    {
        $this->not_migrateable_fields[] = $source_field;
    }
}
