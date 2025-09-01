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

use Override;
use ParagonIE\EasyDB\EasyDB;
use Tuleap\Baseline\Domain\Authorizations;
use Tuleap\Baseline\Domain\Baseline;
use Tuleap\Baseline\Domain\BaselineRepository;
use Tuleap\Baseline\Domain\Comparison;
use Tuleap\Baseline\Domain\ComparisonRepository;
use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Baseline\Domain\TransientComparison;
use Tuleap\Baseline\Domain\UserIdentifier;
use UserManager;

class ComparisonRepositoryAdapter implements ComparisonRepository
{
    /** @var EasyDB */
    private $db;

    /** @var BaselineRepository */
    private $baseline_repository;

    /** @var UserManager */
    private $user_manager;

    /** @var Authorizations */
    private $authorizations;

    /** @var ClockAdapter */
    private $clock;

    public function __construct(
        EasyDB $db,
        BaselineRepository $baseline_repository,
        UserManager $user_manager,
        Authorizations $authorizations,
        ClockAdapter $clock,
    ) {
        $this->db                  = $db;
        $this->baseline_repository = $baseline_repository;
        $this->user_manager        = $user_manager;
        $this->authorizations      = $authorizations;
        $this->clock               = $clock;
    }

    /**
     * Note: Authorizations may have been checked earlier
     */
    #[Override]
    public function add(TransientComparison $comparison, UserIdentifier $current_user): Comparison
    {
        $creation_date = $this->clock->now();

        $id = (int) $this->db->insertReturnId(
            'plugin_baseline_comparison',
            [
                'name'                    => $comparison->getName(),
                'comment'                 => $comparison->getComment(),
                'base_baseline_id'        => $comparison->getBaseBaseline()->getId(),
                'compared_to_baseline_id' => $comparison->getComparedToBaseline()->getId(),
                'user_id'                 => $current_user->getId(),
                'creation_date'           => $creation_date->getTimestamp(),
            ]
        );

        return new Comparison(
            $id,
            $comparison->getName(),
            $comparison->getComment(),
            $comparison->getBaseBaseline(),
            $comparison->getComparedToBaseline(),
            $current_user,
            $creation_date
        );
    }

    #[Override]
    public function findById(UserIdentifier $current_user, int $id): ?Comparison
    {
        $rows = $this->db->safeQuery(
            'SELECT id, name, comment, base_baseline_id, compared_to_baseline_id, user_id, creation_date
            FROM plugin_baseline_comparison
            WHERE id = ?',
            [$id]
        );

        if (count($rows) === 0) {
            return null;
        }

        $comparison = $this->mapRow($current_user, $rows[0]);
        if ($comparison === null) {
            return null;
        }

        if (! $this->authorizations->canReadComparison($current_user, $comparison)) {
            return null;
        }
        return $comparison;
    }

    /**
     * Note: Authorizations may have been checked earlier
     */
    #[Override]
    public function delete(Comparison $comparison, UserIdentifier $current_user): void
    {
        $this->db->delete('plugin_baseline_comparison', ['id' => $comparison->getId()]);
    }

    /**
     * Note: Authorizations may have been checked earlier
     * @return Comparison[]
     */
    #[Override]
    public function findByProject(UserIdentifier $current_user, ProjectIdentifier $project, int $page_size, int $comparison_offset): array
    {
        $rows = $this->db->safeQuery(
            'SELECT
                comparison.id,
                comparison.name,
                comparison.comment,
                comparison.base_baseline_id,
                comparison.compared_to_baseline_id,
                comparison.user_id,
                comparison.creation_date
            FROM plugin_baseline_comparison as comparison
                 INNER JOIN plugin_baseline_baseline as baseline
            ON baseline.id = comparison.base_baseline_id
                 INNER JOIN tracker_artifact as artifact
            ON artifact.id = baseline.artifact_id
                 INNER JOIN tracker
            ON tracker.id = artifact.tracker_id
            WHERE tracker.group_id = ?
            ORDER BY comparison.creation_date DESC
            LIMIT ?
            OFFSET ?',
            [$project->getID(), $page_size, $comparison_offset]
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
            'SELECT COUNT(comparison.id) as nb
            FROM plugin_baseline_comparison as comparison
                 INNER JOIN plugin_baseline_baseline as baseline
            ON baseline.id = comparison.base_baseline_id
                 INNER JOIN tracker_artifact as artifact
            ON artifact.id = baseline.artifact_id
                 INNER JOIN tracker
            ON tracker.id = artifact.tracker_id
            WHERE tracker.group_id = ?',
            [$project->getID()]
        );
    }

    private function mapRow(UserIdentifier $current_user, array $row): ?Comparison
    {
        $base_baseline = $this->baseline_repository->findById($current_user, $row['base_baseline_id']);
        if ($base_baseline === null) {
            return null;
        }

        $compared_to_baseline = $this->baseline_repository->findById($current_user, $row['compared_to_baseline_id']);
        if ($compared_to_baseline === null) {
            return null;
        }

        $user = $this->user_manager->getUserById($row['user_id']);
        if ($user === null) {
            return null;
        }

        $author        = UserProxy::fromUser($user);
        $creation_date = $this->clock->at($row['creation_date']);

        return new Comparison(
            $row['id'],
            $row['name'],
            $row['comment'],
            $base_baseline,
            $compared_to_baseline,
            $author,
            $creation_date
        );
    }

    #[Override]
    public function countByBaseline(Baseline $baseline): int
    {
        return $this->db->single(
            'SELECT COUNT(comparison.id) as nb
            FROM plugin_baseline_comparison as comparison
            WHERE comparison.base_baseline_id = ?
            OR comparison.compared_to_baseline_id = ?',
            [$baseline->getId(), $baseline->getId()]
        );
    }
}
