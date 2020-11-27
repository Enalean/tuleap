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
use Planning;
use Planning_ArtifactLinker;
use PlanningFactory;
use Tracker_Artifact_Redirect;
use Tuleap\Tracker\Artifact\Artifact;

class EventRedirectAfterArtifactCreationOrUpdateHandler
{
    /**
     * @var AgileDashboard_PaneRedirectionExtractor
     */
    private $params_extractor;
    /**
     * @var Planning_ArtifactLinker
     */
    private $artifact_linker;
    /**
     * @var PlanningFactory
     */
    private $planning_factory;
    /**
     * @var RedirectParameterInjector
     */
    private $injector;

    public function __construct(
        AgileDashboard_PaneRedirectionExtractor $params_extractor,
        Planning_ArtifactLinker $artifact_linker,
        PlanningFactory $planning_factory,
        RedirectParameterInjector $injector
    ) {
        $this->params_extractor = $params_extractor;
        $this->artifact_linker  = $artifact_linker;
        $this->planning_factory = $planning_factory;
        $this->injector         = $injector;
    }

    public function process(
        Codendi_Request $request,
        Tracker_Artifact_Redirect $redirect,
        Artifact $artifact
    ): void {
        $last_milestone_artifact = $this->artifact_linker->linkBacklogWithPlanningItems($request, $artifact);

        $requested_planning = $this->params_extractor->extractParametersFromRequest($request);
        if (! $requested_planning) {
            return;
        }

        $planning = $this->planning_factory->getPlanning($requested_planning['planning_id']);

        if ($redirect->stayInTracker()) {
            $this->saveToRequestForFutureRedirection($planning, $last_milestone_artifact, $redirect, $request);
        } else {
            if ($planning) {
                $this->addRedirectionToPlanning($artifact, $requested_planning, $planning, $redirect);
            } else {
                $this->addRedirectionToTopPlanning($artifact, $requested_planning, $redirect);
            }
        }
    }

    /**
     * @psalm-param array{planning_id: string, pane: string, aid: string, pane: string} $requested_planning
     */
    private function addRedirectionToPlanning(
        Artifact $artifact,
        array $requested_planning,
        Planning $planning,
        Tracker_Artifact_Redirect $redirect
    ): void {
        $redirect_to_artifact = $requested_planning[AgileDashboard_PaneRedirectionExtractor::ARTIFACT_ID];
        if ((int) $redirect_to_artifact === -1) {
            $redirect_to_artifact = $artifact->getId();
        }
        $redirect->base_url         = '/plugins/agiledashboard/';
        $redirect->query_parameters = [
            'group_id'    => (string) $planning->getGroupId(),
            'planning_id' => (string) $planning->getId(),
            'action'      => 'show',
            'aid'         => (string) $redirect_to_artifact,
            'pane'        => $requested_planning[AgileDashboard_PaneRedirectionExtractor::PANE],
        ];
    }

    /**
     * @psalm-param array{planning_id: string, pane: string, aid: string, pane: string} $requested_planning
     */
    private function addRedirectionToTopPlanning(
        Artifact $artifact,
        array $requested_planning,
        Tracker_Artifact_Redirect $redirect
    ): void {
        $redirect->base_url         = '/plugins/agiledashboard/';
        $redirect->query_parameters = [
            'group_id' => (string) ($artifact->getTracker()->getProject()->getID()),
            'action'   => 'show-top',
            'pane'     => $requested_planning['pane'],
        ];
    }

    private function saveToRequestForFutureRedirection(
        ?Planning $planning,
        ?Artifact $last_milestone_artifact,
        Tracker_Artifact_Redirect $redirect,
        Codendi_Request $request
    ): void {
        $child_milestone_id = null;
        // Pass the right parameters so parent can be created in the right milestone
        if ($planning && $last_milestone_artifact && $redirect->mode === Tracker_Artifact_Redirect::STATE_CREATE_PARENT) {
            $child_milestone_id = (string) $last_milestone_artifact->getId();
        }

        $this->injector->injectParametersWithGivenChildMilestone($request, $redirect, $child_milestone_id);
    }
}
