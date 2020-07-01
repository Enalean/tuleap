<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot;

use PFUser;
use Psr\Log\LoggerInterface;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\Attachment;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntryItemsRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntryValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\IssueAPIRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;

class InitialSnapshotBuilder
{
    private const EXCLUDED_FIELDS = [
        Tracker_FormElementFactory::FIELD_FILE_TYPE
    ];

    /**
     * @var CreationStateListValueFormatter
     */
    private $creation_state_list_value_formatter;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CreationStateListValueFormatter $creation_state_list_value_formatter,
        LoggerInterface $logger
    ) {
        $this->creation_state_list_value_formatter = $creation_state_list_value_formatter;
        $this->logger = $logger;
    }

    /**
     * @param ChangelogEntryValueRepresentation[] $changelog_entries
     * @param Attachment[] $attachment_collection
     *
     * @throws \Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException
     */
    public function buildInitialSnapshot(
        PFUser $forge_user,
        Snapshot $current_snapshot,
        array $changelog_entries,
        FieldMappingCollection $jira_field_mapping_collection,
        IssueAPIRepresentation $issue_api_representation,
        array $attachment_collection,
        string $jira_base_url
    ): Snapshot {
        $already_seen_fields_keys = [];
        $field_snapshots          = [];

        $this->logger->debug("Build initial snapshot ... ");

        $this->markExcludedFieldsAsAlreadySeen(
            $current_snapshot,
            $already_seen_fields_keys
        );

        foreach ($changelog_entries as $changelog_entry) {
            $this->retrieveInitialFieldsValueInChangelogEntry(
                $changelog_entry,
                $current_snapshot,
                $field_snapshots,
                $already_seen_fields_keys
            );
        }
        $this->logger->debug("Initial fields values built successfully ");

        $this->retrieveFieldsNotModifiedSinceIssueCreation(
            $current_snapshot,
            $field_snapshots,
            $already_seen_fields_keys
        );
        $this->logger->debug("Fields not modified since creation built successfully ");

        $this->addJiraLinkInformation(
            $field_snapshots,
            $jira_field_mapping_collection,
            $issue_api_representation,
            $jira_base_url
        );
        $this->logger->debug("Link to Jira built successfully ");

        //Add attachments
        $this->addAttachments(
            $field_snapshots,
            $jira_field_mapping_collection,
            $attachment_collection
        );

        $initial_snapshot = new Snapshot(
            $forge_user,
            new \DateTimeImmutable($issue_api_representation->getFieldByKey(AlwaysThereFieldsExporter::JIRA_CREATED_NAME)),
            $field_snapshots,
            null
        );

        return $initial_snapshot;
    }

    private function markExcludedFieldsAsAlreadySeen(
        Snapshot $current_snapshot,
        array &$already_seen_fields_keys
    ): void {
        foreach ($current_snapshot->getAllFieldsSnapshot() as $field_snapshot) {
            $field_mapping = $field_snapshot->getFieldMapping();

            if (in_array($field_mapping->getType(), self::EXCLUDED_FIELDS)) {
                $already_seen_fields_keys[$field_mapping->getJiraFieldId()] = true;
            }
        }
    }

    private function retrieveFieldsNotModifiedSinceIssueCreation(
        Snapshot $current_snapshot,
        array &$field_snapshots,
        array &$already_seen_fields_keys
    ): void {
        foreach ($current_snapshot->getAllFieldsSnapshot() as $field_snapshot) {
            $jira_field_id = $field_snapshot->getFieldMapping()->getJiraFieldId();

            if (array_key_exists($jira_field_id, $already_seen_fields_keys)) {
                continue;
            }

            $already_seen_fields_keys[$jira_field_id] = true;
            $field_snapshots[] = $field_snapshot;
        }
    }

    private function retrieveInitialFieldsValueInChangelogEntry(
        ChangelogEntryValueRepresentation $changelog_entry,
        Snapshot $current_snapshot,
        array &$field_snapshots,
        array &$already_seen_fields_keys
    ): void {
        foreach ($changelog_entry->getItemRepresentations() as $changed_field) {
            $changed_field_id       = $changed_field->getFieldId();
            $current_snapshot_field = $current_snapshot->getFieldInSnapshot($changed_field_id);
            if ($this->mustFieldBeCheckedInChangelog($current_snapshot_field, $already_seen_fields_keys)) {
                $already_seen_fields_keys[$changed_field_id] = true;

                if ($current_snapshot_field === null) {
                    $this->logger->debug(" |_ Current snapshot field is null for " . $changed_field_id);
                    continue;
                }

                $changed_field_from        = $changed_field->getFrom();
                $changed_field_from_string = $changed_field->getFromString();

                if ($this->fieldHasNoInitialValue($changed_field)) {
                    $this->logger->debug(" |_ Field " . $changed_field_id . " has no initial value");
                    continue;
                }

                if ($this->fieldListHasInitialValue($changed_field)) {
                    $field_snapshots[] = new FieldSnapshot(
                        $current_snapshot_field->getFieldMapping(),
                        $this->creation_state_list_value_formatter->formatCreationListValue(
                            $changed_field_from
                        ),
                        $current_snapshot_field->getRenderedValue()
                    );
                    $this->logger->debug(" |_ List field " . $changed_field_id . " has an initial value");
                    continue;
                }

                if ($this->fieldTextHasInitialValue($changed_field)) {
                    $field_snapshots[] = new FieldSnapshot(
                        $current_snapshot_field->getFieldMapping(),
                        $changed_field_from_string,
                        null
                    );
                    $this->logger->debug(" |_ Text field " . $changed_field_id . " has an initial value");
                    continue;
                }
            }
        }
    }

    private function addJiraLinkInformation(
        array &$field_snapshots,
        FieldMappingCollection $jira_field_mapping_collection,
        IssueAPIRepresentation $issue_api_representation,
        string $jira_base_url
    ): void {
        $jira_link_field_mapping = $jira_field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_LINK_FIELD_NAME);
        if ($jira_link_field_mapping === null) {
            $this->logger->debug("No mapping found for artifact link");
            return;
        }

        $jira_link = rtrim($jira_base_url, "/") . "/browse/" . urlencode($issue_api_representation->getKey());

        $field_snapshots[] = new FieldSnapshot(
            $jira_link_field_mapping,
            $jira_link,
            null
        );
    }

    /**
     * @param Attachment[] $attachment_collection
     */
    private function addAttachments(
        array &$field_snapshots,
        FieldMappingCollection $jira_field_mapping_collection,
        array $attachment_collection
    ): void {
        $jira_attachment_field_mapping = $jira_field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_ATTACHMENT_NAME);
        if ($jira_attachment_field_mapping === null) {
            $this->logger->debug("No mapping found for attachment");
            return;
        }

        $attachment_ids = [];
        foreach ($attachment_collection as $attachment) {
            $attachment_ids[] = $attachment->getId();
        }

        $field_snapshots[] = new FieldSnapshot(
            $jira_attachment_field_mapping,
            $attachment_ids,
            null
        );
    }

    private function mustFieldBeCheckedInChangelog(
        ?FieldSnapshot $current_snapshot_field,
        array $already_seen_fields_keys
    ): bool {
        return $current_snapshot_field !== null &&
            ! array_key_exists($current_snapshot_field->getFieldMapping()->getJiraFieldId(), $already_seen_fields_keys);
    }

    private function fieldHasNoInitialValue(ChangelogEntryItemsRepresentation $changed_field): bool
    {
        return $changed_field->getFrom() === null &&
            $changed_field->getFromString() === null;
    }

    private function fieldListHasInitialValue(ChangelogEntryItemsRepresentation $changed_field): bool
    {
        return $changed_field->getFrom() !== null;
    }

    private function fieldTextHasInitialValue(ChangelogEntryItemsRepresentation $changed_field): bool
    {
        return $changed_field->getFromString() !== null;
    }
}
