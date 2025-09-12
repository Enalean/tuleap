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
use PFUser;
use Planning;
use Planning_ArtifactLinker;
use Planning_MilestoneFactory;
use Planning_MilestonePaneFactory;
use PlanningFactory;
use Project;
use Tracker_Artifact_Redirect;
use Tuleap\AgileDashboard\Planning\NotFoundException;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Milestone\PaneInfo;

final readonly class EventRedirectAfterArtifactCreationOrUpdateHandler
{
    public function __construct(
        private AgileDashboard_PaneRedirectionExtractor $pane_redirection_extractor,
        private HomeServiceRedirectionExtractor $home_service_redirection_extractor,
        private Planning_ArtifactLinker $artifact_linker,
        private PlanningFactory $planning_factory,
        private RedirectParameterInjector $injector,
        private Planning_MilestoneFactory $milestone_factory,
        private Planning_MilestonePaneFactory $pane_factory,
    ) {
    }

    public function process(
        Codendi_Request $request,
        Tracker_Artifact_Redirect $redirect,
        Artifact $artifact,
    ): void {
        if ($this->home_service_redirection_extractor->mustRedirectToAgiledashboardHomepage($request)) {
            $redirect->base_url         = '/plugins/agiledashboard/';
            $redirect->query_parameters = [
                'group_id' => (string) $artifact->getTracker()->getGroupId(),
            ];

            return;
        }

        $requested_planning      = $this->pane_redirection_extractor->extractParametersFromRequest($request);
        $last_milestone_artifact = $this->artifact_linker->linkBacklogWithPlanningItems(
            $request,
            $artifact,
            $requested_planning
        );

        if (! $requested_planning) {
            return;
        }

        $planning = $this->planning_factory->getPlanning($request->getCurrentUser(), $requested_planning['planning_id']);

        if ($redirect->stayInTracker()) {
            $this->saveToRequestForFutureRedirection($planning, $last_milestone_artifact, $redirect, $request);
        } else {
            if ($planning) {
                $this->addRedirectionToPlanning(
                    $request->getCurrentUser(),
                    $request->getProject(),
                    $requested_planning,
                    $planning,
                    $redirect
                );
            } else {
                $this->addRedirectionToTopPlanning($artifact, $requested_planning, $redirect);
            }
        }
    }

    /**
     * @psalm-param array{planning_id: string, pane: string, aid: string} $requested_planning
     */
    private function addRedirectionToPlanning(
        PFUser $user,
        Project $project,
        array $requested_planning,
        Planning $planning,
        Tracker_Artifact_Redirect $redirect,
    ): void {
        $redirect_to_artifact_id = $requested_planning[AgileDashboard_PaneRedirectionExtractor::ARTIFACT_ID];
        $pane_identifier         = $requested_planning[AgileDashboard_PaneRedirectionExtractor::PANE];

        $pane_info_to_be_redirected_to = $this->getPaneInfoToBeRedirectedTo(
            $user,
            $project,
            $planning,
            $redirect_to_artifact_id,
            $pane_identifier
        );

        if ($pane_info_to_be_redirected_to !== null) {
            $redirect->base_url         = $pane_info_to_be_redirected_to->getUri();
            $redirect->query_parameters = [];
        } else {
            $this->fallbackToLegacyRedirection(
                $redirect,
                $planning,
                $redirect_to_artifact_id,
                $pane_identifier
            );
        }
    }

    private function getPaneInfoToBeRedirectedTo(
        PFUser $user,
        Project $project,
        Planning $planning,
        string $redirect_to_artifact_id,
        string $pane_identifier,
    ): ?PaneInfo {
        $pane_info_to_be_redirected_to = null;
        try {
            $milestone = $this->milestone_factory->getBareMilestone(
                $user,
                $project,
                $planning->getId(),
                (int) $redirect_to_artifact_id
            );
            if (! $milestone->getArtifact()) {
                return null;
            }

            $list_of_pane_info = $this->pane_factory->getListOfPaneInfo($milestone, $user);
            foreach ($list_of_pane_info as $pane_info) {
                if ($pane_info->getIdentifier() === $pane_identifier) {
                    $pane_info_to_be_redirected_to = $pane_info;
                    break;
                }
            }
        } catch (NotFoundException $e) {
            // Do nothing, fallback to legacy redirect
        }

        return $pane_info_to_be_redirected_to;
    }

    private function fallbackToLegacyRedirection(
        Tracker_Artifact_Redirect $redirect,
        Planning $planning,
        string $redirect_to_artifact_id,
        string $pane_identifier,
    ): void {
        $redirect->base_url         = '/plugins/agiledashboard/';
        $redirect->query_parameters = [
            'group_id'    => (string) $planning->getGroupId(),
            'planning_id' => (string) $planning->getId(),
            'action'      => 'show',
            'aid'         => $redirect_to_artifact_id,
            'pane'        => $pane_identifier,
        ];
    }

    /**
     * @psalm-param array{planning_id: string, pane: string, aid: string} $requested_planning
     */
    private function addRedirectionToTopPlanning(
        Artifact $artifact,
        array $requested_planning,
        Tracker_Artifact_Redirect $redirect,
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
        Codendi_Request $request,
    ): void {
        $child_milestone_id = null;
        // Pass the right parameters so parent can be created in the right milestone
        if ($planning && $last_milestone_artifact && $redirect->mode === Tracker_Artifact_Redirect::STATE_CREATE_PARENT) {
            $child_milestone_id = (string) $last_milestone_artifact->getId();
        }

        $this->injector->injectParameters($request, $redirect, $child_milestone_id);
    }
}
