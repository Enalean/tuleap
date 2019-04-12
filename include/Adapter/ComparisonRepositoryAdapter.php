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

    public function __construct(EasyDB $db, AdapterPermissions $adapter_permissions)
    {
        $this->db                  = $db;
        $this->adapter_permissions = $adapter_permissions;
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
}
