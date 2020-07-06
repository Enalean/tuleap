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
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntryValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;

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

    public function __construct(
        CreationStateListValueFormatter $creation_state_list_value_formatter,
        LoggerInterface $logger
    ) {
        $this->creation_state_list_value_formatter = $creation_state_list_value_formatter;
        $this->logger                              = $logger;
    }

    public function buildSnapshotFromChangelogEntry(
        PFUser $forge_user,
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

            if ($changed_field_to !== null) {
                $fields_snapshot[] = new FieldSnapshot(
                    $field_mapping,
                    $this->creation_state_list_value_formatter->formatCreationListValue(
                        $changed_field_to
                    ),
                    null
                );
                $this->logger->debug("  |_ Generate list value for " . $field_id);
                continue;
            }

            if ($changed_field_to_string !== null) {
                $rendered_value = null;
                $field_snapshot = $current_snapshot->getFieldInSnapshot($field_id);
                if (
                    $field_snapshot !== null &&
                    $field_snapshot->getFieldMapping()->getType() === Tracker_FormElementFactory::FIELD_TEXT_TYPE &&
                    $field_snapshot->getValue() === $changed_field_to_string
                ) {
                    $rendered_value = $field_snapshot->getRenderedValue();
                }

                $fields_snapshot[] = new FieldSnapshot(
                    $field_mapping,
                    $changed_field_to_string,
                    $rendered_value
                );

                $this->logger->debug("  |_ Generate string value for " . $field_id);
                continue;
            }
        }

        return new Snapshot(
            $forge_user,
            $changelog_entry->getCreated(),
            $fields_snapshot,
            null
        );
    }
}
