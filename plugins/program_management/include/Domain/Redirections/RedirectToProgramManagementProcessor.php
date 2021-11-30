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

namespace Tuleap\ProgramManagement\Domain\Redirections;

use Tuleap\ProgramManagement\Domain\Events\RedirectUserAfterArtifactCreationOrUpdateEvent;
use Tuleap\ProgramManagement\Domain\ProjectReference;

final class RedirectToProgramManagementProcessor
{
    public static function process(
        ProgramRedirectionParameters $program_management_redirect_manager,
        RedirectUserAfterArtifactCreationOrUpdateEvent $event,
        ProjectReference $project
    ): void {
        if (! $program_management_redirect_manager->isRedirectionNeeded()) {
            return;
        }

        if ($event->isContinueMode()) {
            $event->setProgramIncrementQueryParameter($program_management_redirect_manager);

            return;
        }

        if ($event->isStayMode()) {
            return;
        }

        $event->setBaseUrl('/program_management/' . urlencode($project->getProjectShortName()));
        $event->resetQueryParameters();
    }
}
