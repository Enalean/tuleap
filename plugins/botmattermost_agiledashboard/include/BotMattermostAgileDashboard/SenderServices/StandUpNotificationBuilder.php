<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\BotMattermostAgileDashboard\SenderServices;

use AgileDashboard_Milestone_MilestoneStatusCounter;
use PFUser;
use Planning;
use Planning_Milestone;
use Planning_MilestoneFactory;
use PlanningFactory;
use Project;
use Tuleap\BotMattermost\SenderServices\MarkdownEngine\MarkdownMustacheRenderer;
use Tuleap\BotMattermostAgileDashboard\Presenter\StandUpSummaryPresenter;
use Tuleap\ServerHostname;
use Tuleap\TimezoneRetriever;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\Burndown\BurndownField;

class StandUpNotificationBuilder
{
    private $milestone_factory;
    private $milestone_status_counter;
    private $planning_factory;
    private $renderer;

    public function __construct(
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_Milestone_MilestoneStatusCounter $milestone_status_counter,
        PlanningFactory $planning_factory,
        MarkdownMustacheRenderer $renderer,
    ) {
        $this->milestone_factory        = $milestone_factory;
        $this->milestone_status_counter = $milestone_status_counter;
        $this->planning_factory         = $planning_factory;
        $this->renderer                 = $renderer;
    }

    public function buildNotificationText(PFUser $user, Project $project)
    {
        $last_plannings_for_presenter = [];
        $last_plannings               = $this->planning_factory->getLastLevelPlannings($user, (int) $project->getID());
        $project_name                 = $project->getPublicName();
        $last_planning_name           = '';

        foreach ($last_plannings as $last_planning) {
            $last_plannings_for_presenter['title']                 = sprintf(dgettext('tuleap-botmattermost_agiledashboard', 'Stand-up summary of %1$s in project %2$s'), $last_planning->getName(), $project_name);
            $last_plannings_for_presenter['milestones']            = $this->buildMilestonesForNotification(
                $last_planning,
                $user
            );
            $last_plannings_for_presenter['no_current_milestones'] =  sprintf(dgettext('tuleap-botmattermost_agiledashboard', 'No milestones in %1$s planning in project %2$s'), $last_planning->getName(), $project_name);
        }

        return $this->renderer->renderToString(
            'stand-up-summary',
            new StandUpSummaryPresenter($last_plannings_for_presenter, $project_name, $last_planning_name)
        );
    }

    private function buildMilestonesForNotification(Planning $last_planning, PFUser $user)
    {
        $milestones           = $this->milestone_factory->getAllCurrentMilestones($user, $last_planning);
        $milestones_presenter = [];

        foreach ($milestones as $milestone) {
            $milestone              = $this->milestone_factory->updateMilestoneContextualInfo($user, $milestone);
            $milestones_presenter[] = $this->buildMilestoneForNotification($milestone, $user);
        }

        return $milestones_presenter;
    }

    private function buildMilestoneForNotification(
        Planning_Milestone $milestone,
        PFUser $user,
    ) {
        $linked_artifacts = $this->getLinkedArtifactsWithRecentModification($milestone, $user);
        $tracker_artifact = $milestone->getArtifact();

        $burndown_url    = null;
        $parent_artifact = $tracker_artifact->getParent($user);

        if ($parent_artifact !== null) {
            $burndown_url = $this->getBurndownUrl($parent_artifact, $user);
        }

        if ($burndown_url === null) {
            $burndown_url = $this->getBurndownUrl($tracker_artifact, $user);
        }

        return [
            'cardwall_url'        => $this->getPlanningCardwallUrl($milestone),
            'artifact_title'      => $milestone->getArtifactTitle(),
            'artifact_start_date' => $this->getDate($milestone->getStartDate()),
            'artifact_end_date'   => $this->getDate($milestone->getEndDate()),
            'has_burndown'        => $burndown_url !== null,
            'burndown_url'        => $burndown_url,
            'milestone_infos'     => $this->buildMilestoneInformation($milestone, $user),
            'linked_artifacts'    => $this->buildLinkedArtifactTable($linked_artifacts),
            'has_recent_update'   => (! empty($linked_artifacts)),
        ];
    }

    private function getBurndownUrl(Artifact $artifact, PFUser $user)
    {
        $user_timezone = date_default_timezone_get();

        date_default_timezone_set(TimezoneRetriever::getServerTimezone());
        $burndown = $this->buildBurndownUrl($artifact, $user);
        date_default_timezone_set($user_timezone);

        return $burndown;
    }

    private function buildBurndownUrl(Artifact $artifact, PFUser $user)
    {
        if ($artifact->getABurndownField($user)) {
            $url_query = http_build_query(
                [
                    'formElement' => $artifact->getABurndownField($user)->getId(),
                    'func'        => BurndownField::FUNC_SHOW_BURNDOWN,
                    'src_aid'     => $artifact->getId(),
                ]
            );

            return ServerHostname::HTTPSUrl() . TRACKER_BASE_URL . '/?' . $url_query;
        }

        return null;
    }

    private function getLinkedArtifactsWithRecentModification(Planning_Milestone $milestone, PFUser $user)
    {
        $artifacts = [];

        foreach ($milestone->getLinkedArtifacts($user) as $artifact) {
            if ($this->checkModificationOnArtifact($artifact)) {
                $artifacts[] = $artifact;
            }
        }

        return $artifacts;
    }

    private function getPlanningCardwallUrl(Planning_Milestone $milestone)
    {
        return ServerHostname::HTTPSUrl() . AGILEDASHBOARD_BASE_URL . '/?' . http_build_query(
            [
                'group_id'    => $milestone->getGroupId(),
                'planning_id' => $milestone->getPlanningId(),
                'action'      => 'show',
                'aid'         => $milestone->getArtifactId(),
                'pane'        => 'cardwall',
            ]
        );
    }

    private function checkModificationOnArtifact(Artifact $artifact)
    {
        return $artifact->getLastUpdateDate() > strtotime('-1 day', time());
    }

    private function buildMilestoneInformation(Planning_Milestone $milestone, PFUser $user)
    {
        $status = $this->milestone_status_counter->getStatus($user, $milestone->getArtifactId());

        return [
            'id'             => $this->buildArtifactLink($milestone->getArtifact()),
            'open'           => $status['open'],
            'closed'         => $status['closed'],
            'days_remaining' => $this->getMilestoneDaysRemaining($milestone),
        ];
    }

    private function getMilestoneDaysRemaining(Planning_Milestone $milestone)
    {
        return max($milestone->getDaysUntilEnd(), 0);
    }

    private function getDate($date)
    {
        return date('d M', $date);
    }

    private function getDateTime($date)
    {
        return date('d M H:i', $date);
    }

    private function buildArtifactLink(Artifact $tracker_Artifact)
    {
        return [
            'url'  => ServerHostname::HTTPSUrl() . $tracker_Artifact->getUri(),
            'name' => $tracker_Artifact->getXRef(),
        ];
    }

    private function buildLinkedArtifactTable(array $tracker_artifacts)
    {
        $table_body = [];

        foreach ($tracker_artifacts as $tracker_artifact) {
            $table_body[] = $this->getTrackerArtifactInfo($tracker_artifact);
        }

        return $table_body;
    }

    private function getTrackerArtifactInfo(Artifact $tracker_artifact)
    {
        return [
            'artifact_link' => $this->buildArtifactLink($tracker_artifact),
            'title'         => $tracker_artifact->getTitle(),
            'status'        => $tracker_artifact->getStatus(),
            'last_update'   => $this->getDateTime($tracker_artifact->getLastUpdateDate()),
        ];
    }
}
