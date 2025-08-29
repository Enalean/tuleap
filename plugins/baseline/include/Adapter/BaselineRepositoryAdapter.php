<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

use DateTimeInterface;
use Override;
use ParagonIE\EasyDB\EasyDB;
use Tuleap\Baseline\Domain\Authorizations;
use Tuleap\Baseline\Domain\Baseline;
use Tuleap\Baseline\Domain\BaselineArtifactRepository;
use Tuleap\Baseline\Domain\BaselineRepository;
use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Baseline\Domain\TransientBaseline;
use Tuleap\Baseline\Domain\UserIdentifier;
use UserManager;

class BaselineRepositoryAdapter implements BaselineRepository
{
    /** @var EasyDB */
    private $db;

    /** @var UserManager */
    private $user_manager;

    /** @var BaselineArtifactRepository */
    private $baseline_artifact_repository;

    /** @var Authorizations */
    private $authorizations;

    /** @var ClockAdapter */
    private $clock;

    public function __construct(
        EasyDB $db,
        UserManager $user_manager,
        BaselineArtifactRepository $baseline_artifact_repository,
        Authorizations $authorizations,
        ClockAdapter $clock,
    ) {
        $this->db                           = $db;
        $this->user_manager                 = $user_manager;
        $this->baseline_artifact_repository = $baseline_artifact_repository;
        $this->authorizations               = $authorizations;
        $this->clock                        = $clock;
    }

    /**
     * Note: Authorizations may have been checked earlier
     */
    #[Override]
    public function add(
        TransientBaseline $baseline,
        UserIdentifier $current_user,
        DateTimeInterface $snapshot_date,
    ): Baseline {
        $id = (int) $this->db->insertReturnId(
            'plugin_baseline_baseline',
            [
                'name'          => $baseline->getName(),
                'artifact_id'   => $baseline->getArtifact()->getId(),
                'user_id'       => $current_user->getId(),
                'snapshot_date' => $snapshot_date->getTimestamp(),
            ]
        );

        return new Baseline(
            $id,
            $baseline->getName(),
            $baseline->getArtifact(),
            $snapshot_date,
            $current_user
        );
    }

    #[Override]
    public function findById(UserIdentifier $current_user, int $id): ?Baseline
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

        $baseline = $this->mapRow($current_user, $rows[0]);
        if ($baseline === null) {
            return null;
        }

        if (! $this->authorizations->canReadBaseline($current_user, $baseline)) {
            return null;
        }

        return $baseline;
    }

    /**
     * Note: Authorizations may have been checked earlier
     */
    #[Override]
    public function delete(Baseline $baseline): void
    {
        $this->db->delete('plugin_baseline_baseline', ['id' => $baseline->getId()]);
    }

    /**
     * Note: Authorizations may have been checked earlier
     * @return Baseline[]
     */
    #[Override]
    public function findByProject(
        UserIdentifier $current_user,
        ProjectIdentifier $project,
        int $page_size,
        int $baseline_offset,
    ): array {
        $rows = $this->db->safeQuery(
            'SELECT baseline.id, baseline.name, baseline.artifact_id, baseline.user_id, baseline.snapshot_date
            FROM plugin_baseline_baseline as baseline
                 INNER JOIN tracker_artifact as artifact
            ON artifact.id = baseline.artifact_id
                 INNER JOIN tracker
            ON tracker.id = artifact.tracker_id
            WHERE tracker.group_id = ?
            ORDER BY baseline.snapshot_date DESC
            LIMIT ?
            OFFSET ?',
            [$project->getID(), $page_size, $baseline_offset]
        );

        return array_filter(
            array_map(
                function (array $row) use ($current_user) {
                    return $this->mapRow($current_user, $row);
                },
                $rows
            )
        );
    }

    /**
     * Note: Authorizations may have been check earlier
     */
    #[Override]
    public function countByProject(ProjectIdentifier $project): int
    {
        return $this->db->single(
            'SELECT COUNT(baseline.id) as nb
            FROM plugin_baseline_baseline as baseline
                 INNER JOIN tracker_artifact as artifact
            ON artifact.id = baseline.artifact_id
                 INNER JOIN tracker
            ON tracker.id = artifact.tracker_id
            WHERE tracker.group_id = ?',
            [$project->getID()]
        );
    }

    private function mapRow(UserIdentifier $current_user, array $row): ?Baseline
    {
        $artifact = $this->baseline_artifact_repository->findById($current_user, $row['artifact_id']);
        if ($artifact === null) {
            return null;
        }
        $user = $this->user_manager->getUserById($row['user_id']);
        if ($user === null) {
            return null;
        }

        $author        = UserProxy::fromUser($user);
        $snapshot_date = $this->clock->at($row['snapshot_date']);

        return new Baseline(
            $row['id'],
            $row['name'],
            $artifact,
            $snapshot_date,
            $author
        );
    }
}
