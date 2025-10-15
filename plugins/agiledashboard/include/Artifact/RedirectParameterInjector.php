<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Artifact;

use AgileDashboard_PaneRedirectionExtractor;
use Codendi_Request;
use Response;
use TemplateRenderer;
use Tracker_Artifact_Redirect;
use Tuleap\AgileDashboard\BacklogItem\BacklogItemCreationUpdateInformationLinkPresenter;

final readonly class RedirectParameterInjector
{
    public function __construct(
        private AgileDashboard_PaneRedirectionExtractor $params_extractor,
        private \Tracker_ArtifactFactory $artifact_factory,
        private Response $response,
        private TemplateRenderer $template_renderer,
    ) {
    }

    public function injectAndInformUserAboutBacklogItemWillBeLinked(
        Codendi_Request $request,
        Tracker_Artifact_Redirect $redirect,
    ): void {
        $this->informUserThatTheArtifactWillBeLinkedToTheMilestone($request);

        $child_milestone_id = null;
        if ($request->exist('child_milestone')) {
            $child_milestone_id = $request->getValidated('child_milestone', 'uint', 0);
        }
        $this->injectParameters($request, $redirect, $child_milestone_id);
    }

    public function injectParameters(Codendi_Request $request, Tracker_Artifact_Redirect $redirect, ?string $child_milestone_id): void
    {
        $requested_planning = $this->params_extractor->extractParametersFromRequest($request);
        if ($requested_planning) {
            $key = 'planning[' . $requested_planning[AgileDashboard_PaneRedirectionExtractor::PANE] . '][' . $requested_planning[AgileDashboard_PaneRedirectionExtractor::PLANNING_ID] . ']';

            $redirect->query_parameters[$key] = $requested_planning[AgileDashboard_PaneRedirectionExtractor::ARTIFACT_ID];

            if ($request->get(\Planning_ArtifactLinker::LINK_TO_MILESTONE_PARAMETER)) {
                $redirect->query_parameters[\Planning_ArtifactLinker::LINK_TO_MILESTONE_PARAMETER] = '1';
            }
        }

        if ($child_milestone_id !== null) {
            $redirect->query_parameters['child_milestone'] = $child_milestone_id;
        }
    }

    private function informUserThatTheArtifactWillBeLinkedToTheMilestone(Codendi_Request $request): void
    {
        $requested_planning = $this->params_extractor->extractParametersFromRequest($request);
        if (! $requested_planning) {
            return;
        }

        if (! $request->get(\Planning_ArtifactLinker::LINK_TO_MILESTONE_PARAMETER)) {
            return;
        }

        $milestone_id = $requested_planning[AgileDashboard_PaneRedirectionExtractor::ARTIFACT_ID];

        $milestone_artifact = $this->artifact_factory->getArtifactByIdUserCanView(
            $request->getCurrentUser(),
            $milestone_id
        );

        if (! $milestone_artifact) {
            return;
        }

        $feedback_message = sprintf(
            dgettext('tuleap-agiledashboard', 'You are creating a new backlog item in: %s'),
            $this->template_renderer->renderToString(
                'backlog-item-creation-update-information-link',
                BacklogItemCreationUpdateInformationLinkPresenter::fromArtifact($milestone_artifact)
            )
        );

        $this->response->addFeedback(
            \Feedback::INFO,
            $feedback_message,
            CODENDI_PURIFIER_FULL
        );
    }
}
