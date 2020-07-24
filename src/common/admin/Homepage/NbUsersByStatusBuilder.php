<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Admin\Homepage;

use PFUser;

class NbUsersByStatusBuilder
{
    /**
     * @var UserCounterDao
     */
    private $dao;

    public function __construct(UserCounterDao $dao)
    {
        $this->dao = $dao;
    }

    public function getNbUsersByStatusBuilder()
    {
        $count = $this->getNbUsersByStatus();

        $nb_active               = isset($count[PFUser::STATUS_ACTIVE]) ? $count[PFUser::STATUS_ACTIVE] : 0;
        $nb_restricted           = isset($count[PFUser::STATUS_RESTRICTED]) ? $count[PFUser::STATUS_RESTRICTED] : 0;
        $nb_suspended            = isset($count[PFUser::STATUS_SUSPENDED]) ? $count[PFUser::STATUS_SUSPENDED] : 0;
        $nb_deleted              = isset($count[PFUser::STATUS_DELETED]) ? $count[PFUser::STATUS_DELETED] : 0;
        $nb_pending              = isset($count[PFUser::STATUS_PENDING]) ? $count[PFUser::STATUS_PENDING] : 0;
        $nb_validated            = isset($count[PFUser::STATUS_VALIDATED]) ? $count[PFUser::STATUS_VALIDATED] : 0;
        $nb_validated_restricted = isset($count[PFUser::STATUS_VALIDATED_RESTRICTED]) ? $count[PFUser::STATUS_VALIDATED_RESTRICTED] : 0;

        return new NbUsersByStatus(
            $nb_active,
            $nb_restricted,
            $nb_suspended,
            $nb_deleted,
            $nb_pending,
            $nb_validated,
            $nb_validated_restricted
        );
    }

    /**
     * @return array
     */
    private function getNbUsersByStatus()
    {
        $count = [];
        $dar   = $this->dao->getNbOfUsersByStatus();
        foreach ($dar as $row) {
            $count[$row['status']] = $row['nb'];
        }

        return $count;
    }
}
