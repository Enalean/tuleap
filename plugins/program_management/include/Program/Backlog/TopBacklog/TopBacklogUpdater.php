<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Program\Backlog\TopBacklog;

use Tracker_NoArtifactLinkFieldException;
use Tuleap\ProgramManagement\Program\Backlog\Feature\FeatureHasPlannedUserStoryException;
use Tuleap\ProgramManagement\Program\Program;

class TopBacklogUpdater
{
    /**
     * @var TopBacklogChangeProcessor
     */
    private $top_backlog_change_processor;

    public function __construct(TopBacklogChangeProcessor $top_backlog_change_processor)
    {
        $this->top_backlog_change_processor = $top_backlog_change_processor;
    }

    /**
     * @throws CannotManipulateTopBacklog
     * @throws FeatureHasPlannedUserStoryException
     * @throws Tracker_NoArtifactLinkFieldException
     */
    public function updateTopBacklog(Program $program, TopBacklogChange $top_backlog_change, \PFUser $user): void
    {
        $this->top_backlog_change_processor->processTopBacklogChangeForAProgram($program, $top_backlog_change, $user);
    }
}
