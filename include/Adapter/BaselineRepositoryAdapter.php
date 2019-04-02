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
use Tuleap\Baseline\Baseline;
use Tuleap\Baseline\BaselineArtifactRepository;
use Tuleap\Baseline\BaselineRepository;
use Tuleap\Baseline\NotAuthorizedException;
use Tuleap\Baseline\TransientBaseline;
use UserManager;

class BaselineRepositoryAdapter implements BaselineRepository
{
    /** @var EasyDB */
    private $db;

    /** @var UserManager */
    private $user_manager;

    /** @var BaselineArtifactRepository */
    private $baseline_artifact_repository;

    /** @var AdapterPermissions */
    private $adapter_permissions;

    public function __construct(
        EasyDB $db,
        UserManager $user_manager,
        BaselineArtifactRepository $baseline_artifact_repository,
        AdapterPermissions $adapter_permissions
    ) {
        $this->db                           = $db;
        $this->user_manager                 = $user_manager;
        $this->baseline_artifact_repository = $baseline_artifact_repository;
        $this->adapter_permissions          = $adapter_permissions;
    }

    /**
     * @throws NotAuthorizedException
     */
    public function add(
        TransientBaseline $baseline,
        PFUser $current_user,
        DateTime $snapshot_date
    ): Baseline {

        $project = $baseline->getProject();
        if (! $this->adapter_permissions->canUserAdministrateBaselineOnProject($current_user, $project)) {
            throw new NotAuthorizedException(
                sprintf(
                    dgettext('tuleap-baseline', "You're not allowed to add new baseline in project with id %u"),
                    $project->getID()
                )
            );
        }

        $id = (int) $this->db->insertReturnId(
            'plugin_baseline_baseline',
            [
                'name'          => $baseline->getName(),
                'artifact_id'   => $baseline->getArtifact()->getId(),
                'user_id'       => $current_user->getId(),
                'snapshot_date' => $snapshot_date->getTimestamp()
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

    public function findById(PFUser $current_user, int $id): ?Baseline
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

        if (! $this->adapter_permissions->canUserReadBaselineOnProject($current_user, $baseline->getProject())) {
            return null;
        }
        return $baseline;
    }

    /**
     * @return Baseline[]
     */
    public function findByProject(PFUser $current_user, Project $project, int $page_size, int $baseline_offset): array
    {
        if (! $this->adapter_permissions->canUserReadBaselineOnProject($current_user, $project)) {
            return [];
        }

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

        return array_map(
            function (array $row) use ($current_user) {
                return $this->mapRow($current_user, $row);
            },
            $rows
        );
    }

    public function countByProject(Project $project): int
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

    private function mapRow(PFUser $current_user, array $row): Baseline
    {
        $artifact      = $this->baseline_artifact_repository->findById($current_user, $row['artifact_id']);
        $author        = $this->user_manager->getUserById($row['user_id']);
        $snapshot_date = new DateTime();
        $snapshot_date->setTimestamp($row['snapshot_date'])
            ->setTimezone(new DateTimezone("UTC"));
        return new Baseline(
            $row['id'],
            $row['name'],
            $artifact,
            $snapshot_date,
            $author
        );
    }
}
