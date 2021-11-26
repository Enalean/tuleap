<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Redirections;

use Tracker_Artifact_Redirect;

final class RedirectToPlannedIterationsAppHandler
{
    public function process(
        IterationsRedirectParameters $planned_iterations_redirect_manager,
        Tracker_Artifact_Redirect $redirect,
        \Project $project
    ): void {
        if (! $planned_iterations_redirect_manager->isRedirectionNeeded()) {
            return;
        }

        if ($redirect->mode === Tracker_Artifact_Redirect::STATE_CONTINUE) {
            $redirect->query_parameters[IterationsRedirectParameters::FLAG]               = $planned_iterations_redirect_manager->getRedirectValue();
            $redirect->query_parameters[IterationsRedirectParameters::PARAM_INCREMENT_ID] = $planned_iterations_redirect_manager->getIncrementId();

            return;
        }

        if ($redirect->mode === Tracker_Artifact_Redirect::STATE_STAY) {
            return;
        }

        $redirect->base_url         = '/program_management/' . urlencode($project->getUnixNameMixedCase()) . '/increments/' . urlencode($planned_iterations_redirect_manager->getIncrementId()) . '/plan';
        $redirect->query_parameters = [];
    }
}
