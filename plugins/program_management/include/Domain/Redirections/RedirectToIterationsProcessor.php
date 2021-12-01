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

final class RedirectToIterationsProcessor
{
    public static function process(
        IterationRedirectionParameters $iteration_redirection_parameters,
        RedirectUserAfterArtifactCreationOrUpdateEvent $redirect,
        ProjectReference $project_reference,
    ): void {
        if (! $iteration_redirection_parameters->isRedirectionNeeded()) {
            return;
        }

        if ($redirect->isContinueMode()) {
            $redirect->setQueryParameter($iteration_redirection_parameters);

            return;
        }

        if ($redirect->isStayMode()) {
            return;
        }

        $redirect->setBaseUrl('/program_management/' . urlencode($project_reference->getProjectShortName()) . '/increments/' . urlencode($iteration_redirection_parameters->getIncrementId()) . '/plan');
        $redirect->resetQueryParameters();
    }
}
