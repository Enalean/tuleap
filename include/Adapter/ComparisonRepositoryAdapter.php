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

use ParagonIE\EasyDB\EasyDB;
use PFUser;
use Tuleap\Baseline\BaselineRepository;
use Tuleap\Baseline\Comparison;
use Tuleap\Baseline\ComparisonRepository;
use Tuleap\Baseline\NotAuthorizedException;
use Tuleap\Baseline\TransientComparison;

class ComparisonRepositoryAdapter implements ComparisonRepository
{
    /** @var EasyDB */
    private $db;

    /** @var AdapterPermissions */
    private $adapter_permissions;

    /** @var BaselineRepository */
    private $baseline_repository;

    public function __construct(
        EasyDB $db,
        AdapterPermissions $adapter_permissions,
        BaselineRepository $baseline_repository
    ) {
        $this->db                  = $db;
        $this->adapter_permissions = $adapter_permissions;
        $this->baseline_repository = $baseline_repository;
    }

    /**
     * @throws NotAuthorizedException
     */
    public function add(TransientComparison $comparison, PFUser $current_user): Comparison
    {
        $project = $comparison->getProject();
        if (! $this->adapter_permissions->canUserAdministrateBaselineOnProject($current_user, $project)) {
            throw new NotAuthorizedException(
                sprintf(
                    dgettext('tuleap-baseline', "You're not allowed to add new comparison in project with id %u"),
                    $project->getID()
                )
            );
        }

        $id = (int) $this->db->insertReturnId(
            'plugin_baseline_comparison',
            [
                'name'                    => $comparison->getName(),
                'comment'                 => $comparison->getComment(),
                'base_baseline_id'        => $comparison->getBaseBaseline()->getId(),
                'compared_to_baseline_id' => $comparison->getComparedToBaseline()->getId()
            ]
        );

        return new Comparison(
            $id,
            $comparison->getName(),
            $comparison->getComment(),
            $comparison->getBaseBaseline(),
            $comparison->getComparedToBaseline()
        );
    }

    public function findById(PFUser $current_user, int $id): ?Comparison
    {
        $rows = $this->db->safeQuery(
            'SELECT id, name, comment, base_baseline_id, compared_to_baseline_id
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

        if (! $this->adapter_permissions->canUserReadBaselineOnProject($current_user, $comparison->getProject())) {
            return null;
        }
        return $comparison;
    }

    private function mapRow(PFUser $current_user, array $row): ?Comparison
    {
        $base_baseline = $this->baseline_repository->findById($current_user, $row['base_baseline_id']);
        if ($base_baseline === null) {
            return null;
        }

        $compared_to_baseline = $this->baseline_repository->findById($current_user, $row['compared_to_baseline_id']);
        if ($compared_to_baseline === null) {
            return null;
        }

        return new Comparison(
            $row['id'],
            $row['name'],
            $row['comment'],
            $base_baseline,
            $compared_to_baseline
        );
    }
}
