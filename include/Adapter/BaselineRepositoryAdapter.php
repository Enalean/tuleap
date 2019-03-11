<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Baseline\Adapter;

use DateTime;
use DateTimeZone;
use ParagonIE\EasyDB\EasyDB;
use PFUser;
use Project;
use Tracker_ArtifactFactory;
use Tuleap\Baseline\Baseline;
use Tuleap\Baseline\BaselineRepository;
use Tuleap\Baseline\TransientBaseline;
use UserManager;

class BaselineRepositoryAdapter implements BaselineRepository
{
    public const SQL_COUNT_ALIAS = 'nb';

    /** @var EasyDB */
    private $db;

    /** @var UserManager */
    private $user_manager;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    public function __construct(EasyDB $db, UserManager $user_manager, Tracker_ArtifactFactory $artifact_factory)
    {
        $this->db               = $db;
        $this->user_manager     = $user_manager;
        $this->artifact_factory = $artifact_factory;
    }

    public function add(
        TransientBaseline $baseline,
        PFUser $current_user,
        DateTime $snapshot_date
    ): Baseline {

        $id = (int) $this->db->insertReturnId(
            'plugin_baseline_baseline',
            [
                'name'          => $baseline->getName(),
                'artifact_id'   => $baseline->getMilestone()->getId(),
                'user_id'       => $current_user->getId(),
                'snapshot_date' => $snapshot_date->getTimestamp()
            ]
        );

        return new Baseline(
            $id,
            $baseline->getName(),
            $baseline->getMilestone(),
            $snapshot_date,
            $current_user
        );
    }

    public function findById(int $id): ?Baseline
    {
        $rows = $this->db->safeQuery(
            'SELECT id, name, artifact_id, user_id, snapshot_date
            FROM plugin_baseline_baseline
            WHERE id = ?',
            [$id]
        );

        if (count($rows) === 0) {
            return null;
        }
        return $this->mapRow($rows[0]);
    }

    /**
     * @return Baseline[]
     */
    public function findByProject(Project $project, int $page_size, int $baseline_offset): array
    {
        $rows = $this->db->safeQuery(
            'SELECT baseline.id, baseline.name, baseline.artifact_id, baseline.user_id, baseline.snapshot_date
            FROM plugin_baseline_baseline as baseline
                 INNER JOIN tracker_artifact as artifact
            ON artifact.id = baseline.artifact_id
                 INNER JOIN tracker
            ON tracker.id = artifact.tracker_id
            WHERE tracker.group_id = ?
            ORDER BY baseline.snapshot_date ASC
            LIMIT ?
            OFFSET ?',
            [$project->getID(), $page_size, $baseline_offset]
        );

        return array_map([$this, 'mapRow'], $rows);
    }

    public function countByProject(Project $project): int
    {
        $rows = $this->db->safeQuery(
            "SELECT COUNT(baseline.id) as " . self::SQL_COUNT_ALIAS . "
            FROM plugin_baseline_baseline as baseline
                 INNER JOIN tracker_artifact as artifact
            ON artifact.id = baseline.artifact_id
                 INNER JOIN tracker
            ON tracker.id = artifact.tracker_id
            WHERE tracker.group_id = ?",
            [$project->getID()]
        );
        return $rows[0][self::SQL_COUNT_ALIAS];
    }

    private function mapRow(array $row): Baseline
    {
        $milestone     = $this->artifact_factory->getArtifactById($row['artifact_id']);
        $author        = $this->user_manager->getUserById($row['user_id']);
        $snapshot_date = new DateTime();
        $snapshot_date->setTimestamp($row['snapshot_date'])
            ->setTimezone(new DateTimezone("UTC"));
        return new Baseline(
            $row['id'],
            $row['name'],
            $milestone,
            $snapshot_date,
            $author
        );
    }
}
