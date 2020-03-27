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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\TestManagement\Event;

use PFUser;
use Planning_Milestone;
use Tuleap\Event\Dispatchable;

class GetMilestone implements Dispatchable
{

    public const NAME = 'testmanagement_get_milestone';

    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var int
     */
    private $milestone_id;

    /**
     * @var Planning_Milestone|null
     */
    private $milestone;

    public function __construct(PFUser $user, int $milestone_id)
    {
        $this->user         = $user;
        $this->milestone_id = $milestone_id;
    }

    public function getUser(): PFUser
    {
        return $this->user;
    }

    public function getMilestoneId(): int
    {
        return $this->milestone_id;
    }


    public function getMilestone(): ?Planning_Milestone
    {
        return $this->milestone;
    }

    public function setMilestone(?Planning_Milestone $milestone = null): void
    {
        $this->milestone = $milestone;
    }
}
