<?php
/**
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

use Tuleap\Event\Dispatchable;

final class RootPlanningUpdateIsAllowedEvent implements Dispatchable
{
    public const NAME = 'rootPlanningUpdateIsAllowed';

    /**
     * @var bool
     */
    private $is_allowed = true;
    /**
     * @var \Project
     * @psalm-readonly
     */
    private $project;
    /**
     * @var \Planning
     * @psalm-readonly
     */
    private $original_planning;
    /**
     * @var \PlanningParameters
     * @psalm-readonly
     */
    private $updated_planning;

    public function __construct(\Project $project, \Planning $original_planning, \PlanningParameters $updated_planning)
    {
        $this->project           = $project;
        $this->original_planning = $original_planning;
        $this->updated_planning  = $updated_planning;
    }

    public function disallowPlanningUpdate(): void
    {
        $this->is_allowed = false;
    }

    /**
     * @psalm-mutation-free
     */
    public function isUpdateAllowed(): bool
    {
        return $this->is_allowed;
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
    public function getOriginalPlanning(): \Planning
    {
        return $this->original_planning;
    }

    /**
     * @psalm-mutation-free
     */
    public function getUpdatedPlanning(): \PlanningParameters
    {
        return $this->updated_planning;
    }
}
