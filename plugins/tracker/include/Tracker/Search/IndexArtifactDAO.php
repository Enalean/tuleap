<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Search;

use Tuleap\DB\DataAccessObject;

class IndexArtifactDAO extends DataAccessObject
{
    public function markExistingArtifactsAsPending(): void
    {
        $this->getDB()->run(
            'INSERT INTO plugin_tracker_artifact_pending_indexation(id)
            SELECT tracker_artifact.id
            FROM tracker_artifact
            JOIN tracker ON (tracker_artifact.tracker_id = tracker.id)
            JOIN `groups` ON (tracker.group_id = `groups`.group_id)
            WHERE tracker.deletion_date IS NULL AND `groups`.status != "D"
            ON DUPLICATE KEY UPDATE plugin_tracker_artifact_pending_indexation.id=plugin_tracker_artifact_pending_indexation.id'
        );
    }

    /**
     * @return array{id:int}[]
     */
    public function searchAllPendingArtifactsToIndex(): array
    {
        return $this->getDB()->run('SELECT id FROM plugin_tracker_artifact_pending_indexation');
    }

    public function markPendingArtifactAsProcessed(int $artifact_id): void
    {
        $this->getDB()->run('DELETE FROM plugin_tracker_artifact_pending_indexation WHERE id = ?', $artifact_id);
    }
}
