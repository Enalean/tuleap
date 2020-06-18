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
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;

class CurrentSnapshotBuilder
{
    public function buildCurrentSnapshot(
        PFUser $forge_user,
        array $issue,
        FieldMappingCollection $jira_field_mapping_collection
    ): Snapshot {
        $field_snapshots = [];
        foreach ($issue['fields'] as $key => $value) {
            $rendered_value = $issue['renderedFields'][$key] ?? null;
            $mapping        = $jira_field_mapping_collection->getMappingFromJiraField($key);
            if ($mapping !== null && $value !== null) {
                $field_snapshots[] = new FieldSnapshot(
                    $mapping,
                    $value,
                    $rendered_value
                );
            }
        }

        $current_snapshot = new Snapshot(
            $forge_user,
            new \DateTimeImmutable($issue['fields'][AlwaysThereFieldsExporter::JIRA_UPDATED_ON_NAME]),
            $field_snapshots,
            null
        );

        return $current_snapshot;
    }
}
