<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Widget\Management;

use Tuleap\Tracker\Artifact\RetrieveArtifact;

final class UserTimesForManagerProviderDao extends ManagerPermissionsDao implements RetrieveListOfTimeSpentInArtifact
{
    public function __construct(private readonly RetrieveArtifact $retriever)
    {
        parent::__construct();
    }

    /**
     * @return list<TimeSpentInArtifact>
     */
    #[\Override]
    public function getUserTimesPerArtifact(
        \PFUser $user,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
    ): array {
        $times = $this->getDB()->run(
            <<<EOS
            SELECT times.artifact_id AS artifact_id, SUM(times.minutes) AS minutes
            FROM plugin_timetracking_times AS times
                INNER JOIN tracker_artifact AS artifact ON (
                    times.artifact_id = artifact.id
                    AND times.user_id = ?
                    AND times.day BETWEEN ? AND ?
                )
                INNER JOIN tracker ON (
                    artifact.tracker_id = tracker.id
                    AND tracker.deletion_date IS NULL
                )
            GROUP BY times.artifact_id
            EOS,
            $user->getId(),
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
        );

        $times_spent_in_artifacts = [];
        foreach ($times as $time) {
            $artifact = $this->retriever->getArtifactById($time['artifact_id']);
            if ($artifact === null) {
                continue;
            }

            $times_spent_in_artifacts[] = new TimeSpentInArtifact(
                $user,
                $artifact,
                (int) $time['minutes'],
            );
        }

        return $times_spent_in_artifacts;
    }
}
