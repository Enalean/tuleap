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

namespace Tuleap\Baseline\Adapter;

use DateTime;
use ParagonIE\EasyDB\EasyDB;
use PFUser;
use Tuleap\Baseline\Baseline;
use Tuleap\Baseline\BaselineRepository;
use Tuleap\Baseline\TransientBaseline;

class BaselineRepositoryAdapter implements BaselineRepository
{
    /** @var EasyDB */
    private $db;

    public function __construct(EasyDB $db)
    {
        $this->db = $db;
    }

    public function create(
        TransientBaseline $baseline,
        PFUser $current_user,
        DateTime $creation_date
    ): Baseline {

        $id = (int) $this->db->insertReturnId(
            'plugin_baseline_baseline',
            [
                'name'          => $baseline->getName(),
                'artifact_id'   => $baseline->getMilestone()->getId(),
                'user_id'       => $current_user->getId(),
                'creation_date' => $creation_date->getTimestamp()
            ]
        );

        return new Baseline(
            $id,
            $baseline->getName(),
            $baseline->getMilestone(),
            $current_user,
            $creation_date
        );
    }
}
