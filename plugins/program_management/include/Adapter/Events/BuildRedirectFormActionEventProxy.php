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

namespace Tuleap\ProgramManagement\Adapter\Events;

use Tracker_Artifact_Redirect;
use Tuleap\ProgramManagement\Domain\Events\BuildRedirectFormActionEvent;
use Tuleap\ProgramManagement\Domain\Redirections\IterationRedirectionParameters;
use Tuleap\ProgramManagement\Domain\Redirections\ProgramRedirectionParameters;
use Tuleap\Tracker\Artifact\Renderer\BuildArtifactFormActionEvent;

final class BuildRedirectFormActionEventProxy implements BuildRedirectFormActionEvent
{
    private function __construct(private Tracker_Artifact_Redirect $redirect)
    {
    }

    public static function fromEvent(BuildArtifactFormActionEvent $action_event): self
    {
        return new self($action_event->getRedirect());
    }

    #[\Override]
    public function injectAndInformUserAboutUpdatingProgramItem(): void
    {
        $this->injectAndInformUserAboutProgramItemWillBeUpdated($GLOBALS['Response']);

        $this->redirect->query_parameters[ProgramRedirectionParameters::FLAG] = ProgramRedirectionParameters::REDIRECT_AFTER_UPDATE_ACTION;
    }

    #[\Override]
    public function injectAndInformUserAboutCreatingIteration(IterationRedirectionParameters $iteration_redirection_parameters): void
    {
        $GLOBALS['Response']->addFeedback(
            \Feedback::INFO,
            dgettext(
                'tuleap-program_management',
                'You are creating a new iteration, it will create associated milestones in team projects.'
            )
        );

        $this->redirect->query_parameters[IterationRedirectionParameters::FLAG]               = IterationRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION;
        $this->redirect->query_parameters[IterationRedirectionParameters::PARAM_INCREMENT_ID] = $iteration_redirection_parameters->getIncrementId();
        $this->redirect->query_parameters['link-artifact-id']                                 = $iteration_redirection_parameters->getIncrementId();
        $this->redirect->query_parameters['link-type']                                        = \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::TYPE_IS_CHILD;
        $this->redirect->query_parameters['immediate']                                        = 'true';
    }

    #[\Override]
    public function injectAndInformUserAboutUpdatingIteration(IterationRedirectionParameters $iteration_redirection_parameters): void
    {
        $GLOBALS['Response']->addFeedback(
            \Feedback::INFO,
            dgettext(
                'tuleap-program_management',
                'You are editing an iteration, it will update associated milestones in team projects.'
            )
        );

        $this->redirect->query_parameters[IterationRedirectionParameters::FLAG]               = IterationRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION;
        $this->redirect->query_parameters[IterationRedirectionParameters::PARAM_INCREMENT_ID] = $iteration_redirection_parameters->getIncrementId();
    }

    #[\Override]
    public function injectAndInformUserAboutCreatingProgramIncrement(): void
    {
        $feedback_message = dgettext(
            'tuleap-program_management',
            'You are creating a new program increment, it will create associated milestones in team projects.'
        );

        $GLOBALS['Response']->addFeedback(
            \Feedback::INFO,
            $feedback_message
        );

        $this->redirect->query_parameters[ProgramRedirectionParameters::FLAG] = ProgramRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION;
    }

    private function injectAndInformUserAboutProgramItemWillBeUpdated(\Response $response): void
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
}
