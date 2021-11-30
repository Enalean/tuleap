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
use Tuleap\ProgramManagement\Domain\Redirections\IterationRedirectionParameters;
use Tuleap\ProgramManagement\Domain\Redirections\ProgramRedirectionParameters;

final class RedirectParameterInjector
{
    public function injectAndInformUserAboutUpdatingProgramItem(
        Tracker_Artifact_Redirect $redirect,
        \Response $response
    ): void {
        $this->injectAndInformUserAboutProgramItemWillBeUpdating($response);

        $redirect->query_parameters[ProgramRedirectionParameters::FLAG] = ProgramRedirectionParameters::REDIRECT_AFTER_UPDATE_ACTION;
    }

    public function injectAndInformUserAboutProgramItem(
        Tracker_Artifact_Redirect $redirect,
        \Response $response
    ): void {
        $this->injectAndInformUserAboutProgramItemWillBeCreatedIntoTeams($response);

        $redirect->query_parameters[ProgramRedirectionParameters::FLAG] = ProgramRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION;
    }

    private function injectAndInformUserAboutProgramItemWillBeUpdating(\Response $response): void
    {
        $feedback_message = dgettext(
            'tuleap-program_management',
            'You are updating a program increment, it will update associated milestones in team projects.'
        );

        $response->addFeedback(
            \Feedback::INFO,
            $feedback_message
        );
    }

    private function injectAndInformUserAboutProgramItemWillBeCreatedIntoTeams(\Response $response): void
    {
        $feedback_message = dgettext(
            'tuleap-program_management',
            'You are creating a new program increment, it will create associated milestones in team projects.'
        );

        $response->addFeedback(
            \Feedback::INFO,
            $feedback_message
        );
    }

    public function injectAndInformUserAboutCreatingIncrementIteration(
        Tracker_Artifact_Redirect $redirect,
        \Response $response,
        IterationRedirectionParameters $iteration_redirection_parameters
    ): void {
        $response->addFeedback(
            \Feedback::INFO,
            dgettext(
                'tuleap-program_management',
                'You are creating a new iteration, it will create associated milestones in team projects.'
            )
        );

        $redirect->query_parameters[IterationRedirectionParameters::FLAG]               = IterationRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION;
        $redirect->query_parameters[IterationRedirectionParameters::PARAM_INCREMENT_ID] = $iteration_redirection_parameters->getIncrementId();
        $redirect->query_parameters["link-artifact-id"]                                 = $iteration_redirection_parameters->getIncrementId();
        $redirect->query_parameters["link-type"]                                        = \Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD;
        $redirect->query_parameters["immediate"]                                        = "true";
    }
}
