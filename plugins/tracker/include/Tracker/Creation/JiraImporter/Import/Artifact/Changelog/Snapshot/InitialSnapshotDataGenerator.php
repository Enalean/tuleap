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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\Snapshot;

use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntriesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntryItemsRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntryValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;

class InitialSnapshotDataGenerator
{
    /**
     * @var ChangelogEntriesBuilder
     */
    private $changelog_entries_builder;
    /**
     * @var CreationStateListValueFormatter
     */
    private $creation_state_list_value_formatter;

    public function __construct(
        ChangelogEntriesBuilder $changelog_entries_builder,
        CreationStateListValueFormatter $creation_state_list_value_formatter
    ) {
        $this->changelog_entries_builder           = $changelog_entries_builder;
        $this->creation_state_list_value_formatter = $creation_state_list_value_formatter;
    }

    /**
     * @throws \Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException
     */
    public function generateInitialSnapshotContent(
        Snapshot $current_snapshot,
        string $jira_issue_key
    ): Snapshot {
        $already_seen_fields_keys = [];

        $initial_snapshot = Snapshot::duplicateExistingSnapshot($current_snapshot);

        $changelog_entries = $this->changelog_entries_builder->buildEntriesCollectionForIssue($jira_issue_key);
        foreach ($changelog_entries as $changelog_entry) {
            $this->parseChangelogEntry($changelog_entry, $initial_snapshot, $already_seen_fields_keys);
        }

        return $initial_snapshot;
    }

    private function parseChangelogEntry(
        ChangelogEntryValueRepresentation $changelog_entry,
        Snapshot $initial_snapshot,
        array $already_seen_fields_keys
    ): void {
        foreach ($changelog_entry->getItemRepresentations() as $changed_field) {
            $changed_field_field_id = $changed_field->getFieldId();
            if ($this->mustFieldBeCheckedInChangelog($changed_field_field_id, $initial_snapshot, $already_seen_fields_keys)) {
                $already_seen_fields_keys[$changed_field_field_id] = true;
                $current_snapshot_field                            = $initial_snapshot->getFieldInSnapshot($changed_field_field_id);

                if ($current_snapshot_field === null) {
                    continue;
                }

                $changed_field_from        = $changed_field->getFrom();
                $changed_field_from_string = $changed_field->getFromString();

                if ($this->fieldHasNoInitialValue($changed_field)) {
                    $initial_snapshot->removeFieldSnapshot($changed_field_field_id);
                    continue;
                }

                if ($this->fieldListHasInitialValue($changed_field)) {
                    $initial_snapshot->addFieldSnapshot(
                        new FieldSnapshot(
                            $current_snapshot_field->getFieldMapping(),
                            $this->creation_state_list_value_formatter->formatCreationListValue(
                                $changed_field_from
                            ),
                            $current_snapshot_field->getRenderedValue()
                        )
                    );
                    continue;
                }

                if ($this->fieldTextHasInitialValue($changed_field)) {
                    $initial_snapshot->addFieldSnapshot(
                        new FieldSnapshot(
                            $current_snapshot_field->getFieldMapping(),
                            $changed_field_from_string,
                            null
                        )
                    );
                    continue;
                }
            }
        }
    }

    private function mustFieldBeCheckedInChangelog(
        string $field_id,
        Snapshot $initial_snapshot,
        array $already_seen_fields_keys
    ): bool {
        return $initial_snapshot->isFieldInSnapshot($field_id) &&
            ! array_key_exists($field_id, $already_seen_fields_keys);
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
