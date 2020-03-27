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
use Tuleap\Event\Dispatchable;

class GetItemsFromMilestone implements Dispatchable
{

    public const NAME = 'testmanagement_get_items_from_milestone';

    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var int
     */
    private $milestone_id;

    /**
     * @var array
     */
    private $items_ids;

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


    public function getItemsIds(): array
    {
        return $this->items_ids;
    }

    public function setItemsIds(array $items_ids): void
    {
        $this->items_ids = $items_ids;
    }
}
