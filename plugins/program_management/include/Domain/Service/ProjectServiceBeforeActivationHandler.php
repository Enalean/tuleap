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

namespace Tuleap\ProgramManagement\Domain\Service;

use Tuleap\ProgramManagement\Domain\Events\ProjectServiceBeforeActivationEvent;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;
use Tuleap\ProgramManagement\Domain\Workspace\BacklogBlocksProgramServiceIfNeeded;
use Tuleap\ProgramManagement\Domain\Workspace\ProgramBlocksBacklogServiceIfNeeded;

final class ProjectServiceBeforeActivationHandler
{
    public function __construct(
        private readonly VerifyIsTeam $verify_is_team,
        private readonly BacklogBlocksProgramServiceIfNeeded $program_blocker,
        private readonly ProgramBlocksBacklogServiceIfNeeded $backlog_blocker,
        private readonly string $program_service_shortname,
        private readonly string $backlog_service_shortname,
    ) {
    }

    public function handle(ProjectServiceBeforeActivationEvent $event): void
    {
        if ($event->isForServiceShortName($this->program_service_shortname)) {
            if ($this->verify_is_team->isATeam($event->getProjectIdentifier()->getId())) {
                $event->preventActivation(
                    dgettext('tuleap-program_management', 'Program service cannot be enabled for Team projects.')
                );
                return;
            }

            $this->program_blocker->shouldProgramServiceBeBlocked(
                $event->getUserIdentifier(),
                $event->getProjectIdentifier()
            )->apply($event->preventActivation(...));
            return;
        }
        if (
            $event->isForServiceShortName($this->backlog_service_shortname)
            && $this->backlog_blocker->shouldBacklogServiceBeBlocked($event->getProjectIdentifier())
        ) {
            $event->preventActivation(
                dgettext('tuleap-program_management', 'Backlog service cannot be enabled when the project also uses the Program service.')
            );
        }
    }
}
