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

namespace Tuleap\ProgramManagement;

use Tracker_Artifact_Redirect;

class RedirectParameterInjector
{
    public function injectAndInformUserAboutProgramItem(
        Tracker_Artifact_Redirect $redirect,
        \Response $response
    ): void {
        $this->injectAndInformUserAboutProgramItemWillBeCreatedIntoTeams($response);

        $redirect->query_parameters['program_increment'] = "create";
    }

    private function injectAndInformUserAboutProgramItemWillBeCreatedIntoTeams(\Response $response): void
    {
        $feedback_message = dgettext('tuleap-program_management', 'You are creating a new program increment, it will create associate release increment in team projects');

        $response->addFeedback(
            \Feedback::INFO,
            $feedback_message
        );
    }
}
