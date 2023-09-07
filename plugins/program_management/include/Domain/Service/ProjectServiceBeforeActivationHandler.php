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

final class ProjectServiceBeforeActivationHandler
{
    public function __construct(
        private readonly VerifyIsTeam $verify_is_team,
        private readonly BacklogBlocksProgramServiceIfNeeded $backlog_blocker,
    ) {
    }

    public function handle(ProjectServiceBeforeActivationEvent $event, string $shortname): void
    {
        if (! $event->isForServiceShortName($shortname)) {
            return;
        }

        if ($this->verify_is_team->isATeam($event->getProjectIdentifier()->getId())) {
            $event->preventActivation(
                dgettext('tuleap-program_management', 'Program service cannot be enabled for Team projects.')
            );
        }

        $this->backlog_blocker->shouldProgramServiceBeBlocked(
            $event->getUserIdentifier(),
            $event->getProjectIdentifier()
        )->apply($event->preventActivation(...));
    }
}
