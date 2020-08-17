<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\AgileDashboard\Planning\Admin;

use PFUser;
use Planning;
use Tuleap\Event\Dispatchable;

final class PlanningUpdatedEvent implements Dispatchable
{
    public const NAME = 'planningUpdatedEvent';

    /**
     * @var Planning
     * @psalm-readonly
     */
    private $planning;

    /**
     * @var PFUser
     * @psalm-readonly
     */
    private $user;

    public function __construct(Planning $planning, PFUser $user)
    {
        $this->planning = $planning;
        $this->user     = $user;
    }

    /**
     * @psalm-mutation-free
     */
    public function getPlanning(): Planning
    {
        return $this->planning;
    }

    /**
     * @psalm-mutation-free
     */
    public function getUser(): PFUser
    {
        return $this->user;
    }
}
