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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact;

use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntriesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntryItemsRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;

class CreationStateDataGenerator
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

    public function generateFirstStateContent(
        array $current_state,
        string $jira_issue_key
    ): array {
        $already_seen_fields_keys = [];

        $first_state = $current_state;

        $changelog_entries = $this->changelog_entries_builder->buildEntriesCollectionForIssue($jira_issue_key);
        foreach ($changelog_entries as $changelog_entry) {
            foreach ($changelog_entry->getItemRepresentations() as $changed_field) {
                $changed_field_field_id = $changed_field->getFieldId();
                if ($this->mustFieldBeCheckedInChangelog($changed_field_field_id, $first_state, $already_seen_fields_keys)) {
                    $already_seen_fields_keys[$changed_field_field_id] = true;
                    $changed_field_from        = $changed_field->getFrom();
                    $changed_field_from_string = $changed_field->getFromString();

                    if ($this->fieldHasNoInitialValue($changed_field)) {
                        unset($first_state[$changed_field_field_id]);
                        continue;
                    }

                    if ($this->fieldListHasInitialValue($changed_field)) {
                        $first_state[$changed_field_field_id]['value'] =
                            $this->creation_state_list_value_formatter->formatCreationListValue(
                                $changed_field_from
                            );
                        continue;
                    }

                    if ($this->fieldTextHasInitialValue($changed_field)) {
                        $first_state[$changed_field_field_id]['rendered_value'] = null;
                        $first_state[$changed_field_field_id]['value'] = $changed_field_from_string;
                        continue;
                    }
                }
            }
        }

        return $first_state;
    }

    private function mustFieldBeCheckedInChangelog(
        string $field_id,
        array $first_state,
        array $already_seen_fields_keys
    ): bool {
        return array_key_exists($field_id, $first_state) &&
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
