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

use Psr\Log\LoggerInterface;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntryValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\JiraAuthorRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;

class ChangelogSnapshotBuilder
{
    /**
     * @var CreationStateListValueFormatter
     */
    private $creation_state_list_value_formatter;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JiraAuthorRetriever
     */
    private $jira_author_retriever;

    public function __construct(
        CreationStateListValueFormatter $creation_state_list_value_formatter,
        LoggerInterface $logger,
        JiraAuthorRetriever $jira_author_retriever
    ) {
        $this->creation_state_list_value_formatter = $creation_state_list_value_formatter;
        $this->logger                              = $logger;
        $this->jira_author_retriever               = $jira_author_retriever;
    }

    /**
     * @throws JiraConnectionException
     */
    public function buildSnapshotFromChangelogEntry(
        Snapshot $current_snapshot,
        ChangelogEntryValueRepresentation $changelog_entry,
        AttachmentCollection $attachment_collection,
        FieldMappingCollection $jira_field_mapping_collection
    ): Snapshot {
        $this->logger->debug("Start build snapshot from changelog...");
        $fields_snapshot = [];
        foreach ($changelog_entry->getItemRepresentations() as $item_representation) {
            $field_id      = $item_representation->getFieldId();
            $field_mapping = $jira_field_mapping_collection->getMappingFromJiraField($field_id);

            if ($field_mapping === null) {
                $this->logger->debug("  |_ Field mapping not found for field " . $field_id);
                continue;
            }

            $changed_field_to        = $item_representation->getTo();
            $changed_field_to_string = $item_representation->getToString();

            if (
                $field_mapping->getType() === Tracker_FormElementFactory::FIELD_FILE_TYPE &&
                $field_mapping->getJiraFieldId() === AlwaysThereFieldsExporter::JIRA_ATTACHMENT_NAME &&
                $changed_field_to !== null
            ) {
                $added_attachment_id = (int) $changed_field_to;
                $attachment_ids      = $attachment_collection->getAttachmentIds();

                if (in_array($added_attachment_id, $attachment_ids)) {
                    $fields_snapshot[] = new FieldSnapshot(
                        $field_mapping,
                        [$added_attachment_id],
                        null
                    );
                }

                $this->logger->debug("  |_ Generate file value for " . $field_id);
                continue;
            }

            if (
                $field_mapping->getType() === Tracker_FormElementFactory::FIELD_DATE_TYPE &&
                $changed_field_to !== null
            ) {
                $fields_snapshot[] = new FieldSnapshot(
                    $field_mapping,
                    $changed_field_to,
                    null
                );

                $this->logger->debug("  |_ Generate date value for " . $field_id);
                continue;
            }

            if (
                $field_mapping->getType() === \Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE &&
                $field_mapping->getBindType() === \Tracker_FormElement_Field_List_Bind_Users::TYPE &&
                $changed_field_to !== null
            ) {
                $user              = $this->jira_author_retriever->getAssignedTuleapUser($changed_field_to);
                $fields_snapshot[] = new FieldSnapshot(
                    $field_mapping,
                    $this->creation_state_list_value_formatter->formatListValue(
                        (string) $user->getId()
                    ),
                    null
                );

                $this->logger->debug("  |_ Generate user list value for " . $field_id);
                continue;
            }

            if (
                $field_mapping->getType() === \Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE &&
                $field_mapping->getBindType() === \Tracker_FormElement_Field_List_Bind_Users::TYPE &&
                $changed_field_to !== null
            ) {
                $account_ids = explode(',', $changed_field_to);
                $selected_users_ids = [];

                foreach ($account_ids as $account_id) {
                    $user = $this->jira_author_retriever->getAssignedTuleapUser(
                        trim($account_id)
                    );

                    if ((int) $user->getId() === (int) TrackerImporterUser::ID) {
                        continue;
                    }

                    $selected_users_ids[] = $user->getId();
                }

                $fields_snapshot[] = new FieldSnapshot(
                    $field_mapping,
                    $this->creation_state_list_value_formatter->formatMultiUserListValues(
                        $selected_users_ids
                    ),
                    null
                );

                $this->logger->debug("  |_ Generate multi user list value for " . $field_id);

                continue;
            }

            if ($field_mapping->getJiraFieldId() === AlwaysThereFieldsExporter::JIRA_DESCRIPTION_FIELD_NAME) {
                $fields_snapshot[] = $this->extractFieldSnapshotFromChangesetToString(
                    $current_snapshot,
                    $field_id,
                    $changed_field_to_string,
                    $field_mapping
                );
                continue;
            }

            if ($changed_field_to !== null) {
                $fields_snapshot[] = new FieldSnapshot(
                    $field_mapping,
                    $this->creation_state_list_value_formatter->formatListValue(
                        $changed_field_to
                    ),
                    null
                );
                $this->logger->debug("  |_ Generate list value for " . $field_id);
                continue;
            }

            if ($changed_field_to_string !== null) {
                $fields_snapshot[] = $this->extractFieldSnapshotFromChangesetToString(
                    $current_snapshot,
                    $field_id,
                    $changed_field_to_string,
                    $field_mapping
                );
                continue;
            }
        }

        return new Snapshot(
            $this->jira_author_retriever->retrieveJiraAuthor($changelog_entry->getChangelogOwner()),
            $changelog_entry->getCreated(),
            $fields_snapshot,
            null
        );
    }

    private function extractFieldSnapshotFromChangesetToString(
        Snapshot $current_snapshot,
        string $field_id,
        string $changed_field_to_string,
        FieldMapping $field_mapping
    ): FieldSnapshot {
        $rendered_value = null;
        $field_snapshot = $current_snapshot->getFieldInSnapshot($field_id);
        if (
            $field_snapshot !== null &&
            $field_snapshot->getFieldMapping()->getType() === Tracker_FormElementFactory::FIELD_TEXT_TYPE &&
            $field_snapshot->getValue() === $changed_field_to_string
        ) {
            $rendered_value = $field_snapshot->getRenderedValue();
        }

        $field_snapshot = new FieldSnapshot(
            $field_mapping,
            $changed_field_to_string,
            $rendered_value
        );

        $this->logger->debug("  |_ Generate string value for " . $field_id);
        return $field_snapshot;
    }
}
