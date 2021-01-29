<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile;

use Codendi_Request;
use Tracker_Artifact_Redirect;
use Tuleap\Tracker\Artifact\Artifact;

class EventRedirectAfterArtifactCreationOrUpdateHandler
{
    public function process(
        Codendi_Request $request,
        Tracker_Artifact_Redirect $redirect,
        Artifact $artifact
    ): void {
        $redirect_in_service = $request->get('program_increment') && $request->get('program_increment') === "create";
        if (! $redirect_in_service) {
            return;
        }

        if ($redirect->mode === Tracker_Artifact_Redirect::STATE_CONTINUE) {
            $redirect->query_parameters['program_increment'] = "create";

            return;
        }

        if ($redirect->mode === Tracker_Artifact_Redirect::STATE_STAY) {
            return;
        }

        $project_unixname  = $artifact->getTracker()->getProject()->getUnixNameMixedCase();
        $redirect_base_url = '/scaled_agile/' . urlencode($project_unixname);

        $redirect->base_url         = $redirect_base_url;
        $redirect->query_parameters = [];
    }
}
