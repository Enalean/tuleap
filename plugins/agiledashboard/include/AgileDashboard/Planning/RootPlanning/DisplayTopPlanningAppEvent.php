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

namespace Tuleap\AgileDashboard\Planning\RootPlanning;

use PFUser;
use Planning_VirtualTopMilestone;
use Tuleap\Event\Dispatchable;

class DisplayTopPlanningAppEvent implements Dispatchable
{
    public const NAME = 'displayTopPlanningAppEvent';

    /**
     * @var Planning_VirtualTopMilestone
     * @psalm-readonly
     */
    private $top_milestone;

    /**
     * @var PFUser
     * @psalm-readonly
     */
    private $user;

    /**
     * @var bool
     */
    private $user_can_create_milestone;

    /**
     * @var bool
     */
    private $backlog_items_can_be_added = true;

    public function __construct(Planning_VirtualTopMilestone $top_milestone, PFUser $user)
    {
        $this->top_milestone             = $top_milestone;
        $this->user_can_create_milestone = $this->top_milestone->getPlanning()->getPlanningTracker()->userCanSubmitArtifact($user);
        $this->user                      = $user;
    }

    /**
     * @psalm-mutation-free
     */
    public function getTopMilestone(): Planning_VirtualTopMilestone
    {
        return $this->top_milestone;
    }

    /**
     * @psalm-mutation-free
     */
    public function getUser(): PFUser
    {
        return $this->user;
    }

    public function canUserCreateMilestone(): bool
    {
        return $this->user_can_create_milestone;
    }

    public function setUserCannotCreateMilestone(): void
    {
        $this->user_can_create_milestone = false;
    }

    public function canBacklogItemsBeAdded(): bool
    {
        return $this->backlog_items_can_be_added;
    }

    public function setBacklogItemsCannotBeAdded(): void
    {
        $this->backlog_items_can_be_added = false;
    }
}
