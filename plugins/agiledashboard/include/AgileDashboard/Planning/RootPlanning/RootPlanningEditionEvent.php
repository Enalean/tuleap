<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Planning\RootPlanning;

use Tuleap\AgileDashboard\Planning\Admin\ModificationBan;
use Tuleap\Event\Dispatchable;

final class RootPlanningEditionEvent implements Dispatchable
{
    public const NAME = "rootPlanningEditionEvent";
    /**
     * @var \Project
     * @psalm-readonly
     */
    private $project;
    /**
     * @var \Planning
     * @psalm-readonly
     */
    private $planning;
    /**
     * @var ModificationBan | null
     */
    private $milestone_tracker_modification_ban = null;

    public function __construct(\Project $project, \Planning $planning)
    {
        $this->project  = $project;
        $this->planning = $planning;
    }

    /**
     * @psalm-mutation-free
     */
    public function getProject(): \Project
    {
        return $this->project;
    }

    /**
     * @psalm-mutation-free
     */
    public function getPlanning(): \Planning
    {
        return $this->planning;
    }

    public function prohibitMilestoneTrackerModification(ModificationBan $ban): void
    {
        $this->milestone_tracker_modification_ban = $ban;
    }

    /**
     * @psalm-mutation-free
     */
    public function getMilestoneTrackerModificationBan(): ?ModificationBan
    {
        return $this->milestone_tracker_modification_ban;
    }
}
