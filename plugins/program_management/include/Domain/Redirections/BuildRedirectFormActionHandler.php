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

use Tuleap\ProgramManagement\Domain\Events\BuildRedirectFormActionEvent;

final class BuildRedirectFormActionHandler
{
    public static function injectParameters(
        ProgramRedirectionParameters $redirection_to_program_management,
        IterationRedirectionParameters $redirection_to_iteration,
        BuildRedirectFormActionEvent $event
    ): void {
        if (! $redirection_to_program_management->isRedirectionNeeded() && ! $redirection_to_iteration->isRedirectionNeeded()) {
            return;
        }

        if ($redirection_to_program_management->needsRedirectionAfterUpdate()) {
            $event->injectAndInformUserAboutUpdatingProgramItem();

            return;
        }

        if ($redirection_to_iteration->needsRedirectionAfterCreate()) {
            $event->injectAndInformUserAboutCreatingIteration($redirection_to_iteration);

            return;
        }

        $event->injectAndInformUserAboutCreatingProgramIncrement();
    }
}
