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

namespace Tuleap\ProgramManagement\Domain\Workspace;

use Tuleap\ProgramManagement\Domain\Events\CollectLinkedProjectsEvent;
use Tuleap\ProgramManagement\Domain\Program\VerifyIsProgram;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;

final readonly class CollectLinkedProjectsHandler
{
    public function __construct(
        private VerifyIsProgram $program_verifier,
        private VerifyIsTeam $team_verifier,
        private VerifyProgramServiceIsEnabled $service_verifier,
    ) {
    }

    public function handle(CollectLinkedProjectsEvent $event): void
    {
        $source_project_id = $event->getSourceProject()->getId();
        if ($this->service_verifier->hasProgramEnabled($source_project_id)) {
            $event->projectCanAggregateProjects();
        }
        if ($this->program_verifier->isAProgram($source_project_id)) {
            $event->addTeams();
            return;
        }
        if ($this->team_verifier->isATeam($source_project_id)) {
            $event->addPrograms();
        }
    }
}
