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

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
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
        private readonly EventDispatcherInterface $event_dispatcher,
        private readonly RetrieveUsedFields $retrieve_source_tracker_used_fields,
        private readonly RetrieveUsedFields $retrieve_destination_tracker_used_fields,
        private readonly VerifyFieldCanBeEasilyMigrated $verify_field_can_be_easily_migrated,
        private readonly VerifyIsStaticListField $verify_is_static_list_field,
        private readonly VerifyListFieldsAreCompatible $verify_list_fields_are_compatible,
        private readonly VerifyStaticFieldValuesCanBeFullyMoved $verify_static_field_values_can_be_fully_moved,
        private readonly VerifyIsUserListField $verify_is_user_list_field,
        private readonly VerifyUserFieldValuesCanBeFullyMoved $verify_user_field_values_can_be_fully_moved,
        private readonly VerifyIsUserGroupListField $verify_is_user_group_list_field,
        private readonly VerifyUserGroupValuesCanBeFullyMoved $verify_user_group_field_values_can_be_fully_moved,
        private readonly VerifyIsPermissionsOnArtifactField $verify_is_permissions_on_artifact_field,
        private readonly VerifyThereArePermissionsToMigrate $verify_are_permissions_to_migrate,
        private readonly VerifyPermissionsCanBeFullyMoved $verify_permissions_can_be_fully_moved,
        private readonly VerifyIsOpenListField $verify_is_open_list_field,
        private readonly VerifyOpenListFieldsAreCompatible $verify_open_list_fields_are_compatible,
        private readonly VerifyIsExternalField $verify_is_external_field,
        private readonly VerifyExternalFieldsHaveSameType $verify_external_fields_have_same_type,
    ) {
    }

    public function collect(\Tracker $source_tracker, \Tracker $destination_tracker, Artifact $artifact, \PFUser $user, LoggerInterface $logger): DuckTypedMoveFieldCollection
    {
        foreach ($this->retrieve_source_tracker_used_fields->getUsedFields($source_tracker) as $source_field) {
            $destination_field = $this->retrieve_destination_tracker_used_fields->getUsedFieldByName($destination_tracker->getId(), $source_field->getName());
            if ($destination_field === null) {
                $this->addFieldToNotMigrateableList($source_field);
                continue;
            }

            if ($destination_field->isUpdateable() && ! $destination_field->userCanUpdate($user)) {
                $this->addFieldToNotMigrateableList($source_field);
                continue;
            }

            if (
                $this->verify_is_external_field->isAnExternalField($source_field) &&
                $this->verify_is_external_field->isAnExternalField($destination_field)
            ) {
                $this->collectExternalFields($source_field, $destination_field);
                continue;
            }

            if ($this->verify_field_can_be_easily_migrated->canFieldBeEasilyMigrated($source_field, $destination_field)) {
                $this->addFieldToMigrateableList($source_field, $destination_field);

                continue;
            }

            if (
                $this->verify_is_open_list_field->isAnOpenListField($source_field)
                && $this->verify_is_open_list_field->isAnOpenListField($destination_field)
            ) {
                assert($source_field instanceof \Tracker_FormElement_Field_OpenList);
                assert($destination_field instanceof \Tracker_FormElement_Field_OpenList);
                $this->collectOpenListFields($source_field, $destination_field);
                continue;
            }

            if (
                $this->verify_is_static_list_field->isStaticListField($source_field)
                && $this->verify_is_static_list_field->isStaticListField($destination_field)
            ) {
                assert($source_field instanceof \Tracker_FormElement_Field_List);
                assert($destination_field instanceof \Tracker_FormElement_Field_List);
                $this->collectStaticFields($source_field, $destination_field, $artifact);
                continue;
            }

            if (
                $this->verify_is_user_list_field->isUserListField($source_field)
                && $this->verify_is_user_list_field->isUserListField($destination_field)
            ) {
                assert($source_field instanceof \Tracker_FormElement_Field_List);
                assert($destination_field instanceof \Tracker_FormElement_Field_List);
                $this->collectUserBoundFields($source_field, $destination_field, $artifact);
                continue;
            }

            if (
                $this->verify_is_user_group_list_field->isUserGroupListField($source_field)
                && $this->verify_is_user_group_list_field->isUserGroupListField($destination_field)
            ) {
                assert($source_field instanceof \Tracker_FormElement_Field_List);
                assert($destination_field instanceof \Tracker_FormElement_Field_List);
                $this->collectUserGroupBoundFields($source_field, $destination_field, $artifact);
                continue;
            }

            if (
                $this->verify_is_permissions_on_artifact_field->isPermissionsOnArtifactField($source_field)
                && $this->verify_is_permissions_on_artifact_field->isPermissionsOnArtifactField($destination_field)
            ) {
                assert($source_field instanceof \Tracker_FormElement_Field_PermissionsOnArtifact);
                assert($destination_field instanceof \Tracker_FormElement_Field_PermissionsOnArtifact);
                $this->collectPermissionsOnArtifactFields($source_field, $destination_field, $artifact);
                continue;
            }

            $this->addFieldToNotMigrateableList($source_field);
        }

        $logger->debug(sprintf("Fields totally migratable: %s", implode(',', array_column($this->migrateable_fields, 'label', 'id'))));
        $logger->debug(sprintf("Fields partially migratable: %s", implode(',', array_column($this->partially_migrated_fields, 'label', 'id'))));
        $logger->debug(sprintf("Fields not migratable: %s", implode(',', array_column($this->not_migrateable_fields, 'label', 'id'))));
        return DuckTypedMoveFieldCollection::fromFields($this->migrateable_fields, $this->not_migrateable_fields, $this->partially_migrated_fields, $this->fields_mapping);
    }

    private function collectStaticFields(
        \Tracker_FormElement_Field_List $source_field,
        \Tracker_FormElement_Field_List $destination_field,
        Artifact $artifact,
    ): void {
        if (! $this->verify_list_fields_are_compatible->areListFieldsCompatible($source_field, $destination_field)) {
            $this->addFieldToNotMigrateableList($source_field);
            return;
        }

        if (! $this->verify_static_field_values_can_be_fully_moved->canAllStaticFieldValuesBeMoved($source_field, $destination_field, $artifact)) {
            $this->addFieldToPartiallyMigratedList($source_field, $destination_field);
            return;
        }

        $this->addFieldToMigrateableList($source_field, $destination_field);
    }

    private function collectUserBoundFields(
        \Tracker_FormElement_Field_List $source_field,
        \Tracker_FormElement_Field_List $destination_field,
        Artifact $artifact,
    ): void {
        if (! $this->verify_list_fields_are_compatible->areListFieldsCompatible($source_field, $destination_field)) {
            $this->addFieldToNotMigrateableList($source_field);
            return;
        }

        if (! $this->verify_user_field_values_can_be_fully_moved->canAllUserFieldValuesBeMoved($source_field, $destination_field, $artifact)) {
            $this->addFieldToPartiallyMigratedList($source_field, $destination_field);
            return;
        }

        $this->addFieldToMigrateableList($source_field, $destination_field);
    }

    private function collectUserGroupBoundFields(
        \Tracker_FormElement_Field_List $source_field,
        \Tracker_FormElement_Field_List $destination_field,
        Artifact $artifact,
    ): void {
        if (! $this->verify_list_fields_are_compatible->areListFieldsCompatible($source_field, $destination_field)) {
            $this->addFieldToNotMigrateableList($source_field);
            return;
        }

        if (! $this->verify_user_group_field_values_can_be_fully_moved->canAllUserGroupFieldValuesBeMoved($source_field, $destination_field, $artifact)) {
            $this->addFieldToPartiallyMigratedList($source_field, $destination_field);
            return;
        }

        $this->addFieldToMigrateableList($source_field, $destination_field);
    }

    private function collectPermissionsOnArtifactFields(
        \Tracker_FormElement_Field_PermissionsOnArtifact $source_field,
        \Tracker_FormElement_Field_PermissionsOnArtifact $destination_field,
        Artifact $artifact,
    ): void {
        if (! $this->verify_are_permissions_to_migrate->areTherePermissionsToMigrate($source_field, $artifact)) {
            $this->addFieldToNotMigrateableList($source_field);
            return;
        }

        if (! $this->verify_permissions_can_be_fully_moved->canAllPermissionsBeFullyMoved($source_field, $destination_field, $artifact)) {
            $this->addFieldToPartiallyMigratedList($source_field, $destination_field);
            return;
        }

        $this->addFieldToMigrateableList($source_field, $destination_field);
    }

    private function collectOpenListFields(
        \Tracker_FormElement_Field_OpenList $source_field,
        \Tracker_FormElement_Field_OpenList $destination_field,
    ): void {
        if (! $this->verify_open_list_fields_are_compatible->areOpenListFieldsCompatible($source_field, $destination_field)) {
            $this->addFieldToNotMigrateableList($source_field);
            return;
        }

        $this->addFieldToMigrateableList($source_field, $destination_field);
    }

    private function collectExternalFields(
        \Tracker_FormElement_Field $source_field,
        \Tracker_FormElement_Field $destination_field,
    ): void {
        if (! $this->verify_external_fields_have_same_type->haveBothFieldsSameType($source_field, $destination_field)) {
            $this->addFieldToNotMigrateableList($source_field);
            return;
        }

        $event = new CollectMovableExternalFieldEvent($source_field, $destination_field);
        $this->event_dispatcher->dispatch($event);

        if (! $event->isFieldMigrateable()) {
            $this->addFieldToNotMigrateableList($source_field);
            return;
        }

        if (! $event->isFieldFullyMigrateable()) {
            $this->addFieldToPartiallyMigratedList($source_field, $destination_field);
            return;
        }

        $this->addFieldToMigrateableList($source_field, $destination_field);
    }

    private function addFieldToMigrateableList(\Tracker_FormElement_Field $source_field, \Tracker_FormElement_Field $destination_field): void
    {
        $this->fields_mapping[]     = FieldMapping::fromFields($source_field, $destination_field);
        $this->migrateable_fields[] = $source_field;
    }

    private function addFieldToPartiallyMigratedList(\Tracker_FormElement_Field $source_field, \Tracker_FormElement_Field $destination_field): void
    {
        $this->partially_migrated_fields[] = $source_field;
        $this->fields_mapping[]            = FieldMapping::fromFields($source_field, $destination_field);
    }

    private function addFieldToNotMigrateableList(\Tracker_FormElement_Field $source_field): void
    {
        $this->not_migrateable_fields[] = $source_field;
    }
}
