<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use PFUser;
use Planning;
use Tuleap\AgileDashboard\Planning\RetrieveRootPlanning;

final class RetrieveRootPlanningStub implements RetrieveRootPlanning
{
    private function __construct(private int $team_project_id, private int $backlog_tracker_id)
    {
    }

    public static function withProjectAndBacklogTracker(int $team_project_id, int $backlog_tracker_id): self
    {
        return new self($team_project_id, $backlog_tracker_id);
    }

    public function getRootPlanning(PFUser $user, int $group_id): Planning|false
    {
        if ($group_id !== $this->team_project_id) {
            return false;
        }
        return new Planning(
            34,
            'Release plan',
            $this->team_project_id,
            'Backlog',
            'Releases',
            [$this->backlog_tracker_id],
            1
        );
    }
}
