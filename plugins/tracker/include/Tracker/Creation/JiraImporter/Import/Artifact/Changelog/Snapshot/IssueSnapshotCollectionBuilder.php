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

use PFUser;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;

class IssueSnapshotCollectionBuilder
{
    /**
     * @var InitialSnapshotBuilder
     */
    private $initial_snapshot_builder;

    /**
     * @var CurrentSnapshotBuilder
     */
    private $current_snapshot_builder;

    public function __construct(
        CurrentSnapshotBuilder $current_snapshot_builder,
        InitialSnapshotBuilder $initial_snapshot_builder
    ) {
        $this->initial_snapshot_builder = $initial_snapshot_builder;
        $this->current_snapshot_builder = $current_snapshot_builder;
    }

    /**
     * @return Snapshot[]
     */
    public function buildCollectionOfSnapshotsForIssue(
        PFUser $forge_user,
        array $jira_issue_api,
        FieldMappingCollection $jira_field_mapping_collection
    ): array {
        $snapshots_collection = [];
        $current_snapshot = $this->current_snapshot_builder->buildCurrentSnapshot(
            $forge_user,
            $jira_issue_api,
            $jira_field_mapping_collection
        );
        $snapshots_collection[$current_snapshot->getDate()->getTimestamp()] = $current_snapshot;

        $initial_snapshot = $this->initial_snapshot_builder->buildInitialSnapshot(
            $forge_user,
            $current_snapshot,
            $jira_issue_api,
        );

        $snapshots_collection[$initial_snapshot->getDate()->getTimestamp()] = $initial_snapshot;

        ksort($snapshots_collection);

        return $snapshots_collection;
    }
}
