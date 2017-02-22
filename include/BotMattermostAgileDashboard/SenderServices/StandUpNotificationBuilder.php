<?php
/**
 * Copyright (c) Enalean, 2016-2017. All Rights Reserved.
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

use BaseLanguage;
use HTTPRequest;
use PFUser;
use PlanningFactory;
use Planning;
use Planning_MilestoneFactory;
use Planning_Milestone;
use AgileDashboard_Milestone_MilestoneStatusCounter;
use Project;
use Tracker_Artifact;
use Tracker_FormElement_Field_Burndown;
use Tuleap\BotMattermost\SenderServices\MarkdownEngine\MarkdownMustacheRenderer;
use Tuleap\BotMattermostAgileDashboard\Presenter\StandUpSummaryPresenter;
use Tuleap\TimezoneRetriever;


class StandUpNotificationBuilder
{
    private $milestone_factory;
    private $milestone_status_counter;
    private $planning_factory;
    private $language;
    private $renderer;

    public function __construct(
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_Milestone_MilestoneStatusCounter $milestone_status_counter,
        PlanningFactory $planning_factory,
        BaseLanguage $language,
        MarkdownMustacheRenderer $renderer
    ) {
        $this->milestone_factory        = $milestone_factory;
        $this->milestone_status_counter = $milestone_status_counter;
        $this->planning_factory         = $planning_factory;
        $this->language                 = $language;
        $this->renderer                 = $renderer;
    }

    public function buildNotificationText(HTTPRequest $http_request, PFUser $user, Project $project)
    {
        $last_plannings_for_presenter = array();
        $last_plannings               = $this->planning_factory->getLastLevelPlannings($user, $project->getID());
        $project_name                 = $project->getPublicName();

        foreach ($last_plannings as $last_planning) {
            $last_plannings_for_presenter['title']      = $this->language->getText(
                'plugin_botmattermost_agiledashboard',
                'notification_builder_title_stand_up_summary',
                array($last_planning->getName(), $project_name)
            );
            $last_plannings_for_presenter['milestones'] = $this->buildMilestonesForNotification(
                $http_request, $last_planning, $user
            );
            $last_plannings_for_presenter['no_current_milestones'] =  $this->language->getText(
                'plugin_botmattermost_agiledashboard',
                'notification_builder_no_current_milestones',
                array($last_planning->getName(), $project_name)
            );
        }

        return $this->renderer->renderToString(
            'stand-up-summary',
            new StandUpSummaryPresenter($last_plannings_for_presenter, $project_name)
        );
    }

    private function buildMilestonesForNotification(HTTPRequest $http_request, Planning $last_planning, PFUser $user)
    {
        $milestones           = $this->milestone_factory->getAllCurrentMilestones($user, $last_planning);
        $milestones_presenter = array();

        foreach ($milestones as $milestone) {
            $milestone              = $this->milestone_factory->updateMilestoneContextualInfo($user, $milestone);
            $milestones_presenter[] = $this->buildMilestoneForNotification($http_request, $milestone, $user);
        }

        return $milestones_presenter;
    }

    private function buildMilestoneForNotification(
        HTTPRequest $http_request,
        Planning_Milestone $milestone,
        PFUser $user
    ) {
        $linked_artifacts = $this->getLinkedArtifactsWithRecentModification($milestone, $user);

        return array(
            'cardwall_url'        => $this->getPlanningCardwallUrl($http_request, $milestone),
            'artifact_title'      => $milestone->getArtifactTitle(),
            'artifact_start_date' => $this->getDate($milestone->getStartDate()),
            'artifact_end_date'   => $this->getDate($milestone->getEndDate()),
            'burndown_url'        => $this->getBurndownUrl($http_request, $milestone, $user),
            'milestone_infos'     => $this->buildMilestoneInformation($http_request, $milestone, $user),
            'linked_artifacts'    => $this->buildLinkedArtifactTable($http_request, $linked_artifacts),
            'has_recent_update'   => (! empty($linked_artifacts))
        );
    }

    private function getBurndownUrl(HTTPRequest $http_request, Planning_Milestone $milestone, PFUser $user)
    {
        $user_timezone = date_default_timezone_get();

        date_default_timezone_set(TimezoneRetriever::getServerTimezone());
        $burndown = $this->buildBurndownUrl($http_request, $milestone->getArtifact(), $user);
        date_default_timezone_set($user_timezone);

        return $burndown;
    }

    private function buildBurndownUrl(HTTPRequest $http_request, Tracker_Artifact $artifact, PFUser $user)
    {
        $url_query = http_build_query(
            array(
                'formElement' => $artifact->getABurndownField($user)->getId(),
                'func'        => Tracker_FormElement_Field_Burndown::FUNC_SHOW_BURNDOWN,
                'src_aid'     => $artifact->getId()
            )
        );

        return $http_request->getServerUrl().TRACKER_BASE_URL.'/?'.$url_query;
    }

    private function getLinkedArtifactsWithRecentModification(Planning_Milestone $milestone, PFUser $user)
    {
        $artifacts = array();

        foreach ($milestone->getLinkedArtifacts($user) as $artifact) {
            if ($this->checkModificationOnArtifact($artifact)) {
                $artifacts[] = $artifact;
            }
        }

        return $artifacts;
    }

    private function getPlanningCardwallUrl(HTTPRequest $http_request, Planning_Milestone $milestone)
    {
        return $http_request->getServerUrl().AGILEDASHBOARD_BASE_URL.'/?'.http_build_query(
            array(
                'group_id'    => $milestone->getGroupId(),
                'planning_id' => $milestone->getPlanningId(),
                'action'      => 'show',
                'aid'         => $milestone->getArtifactId(),
                'pane'        => 'cardwall'
            )
        );
    }

    private function checkModificationOnArtifact(Tracker_Artifact $artifact)
    {
        return $artifact->getLastUpdateDate() > strtotime('-1 day', time());
    }

    private function buildMilestoneInformation(HTTPRequest $http_request, Planning_Milestone $milestone, PFUser $user)
    {
        $status = $this->milestone_status_counter->getStatus($user, $milestone->getArtifactId());

        return array(
            'id'             => $this->buildArtifactLink($http_request, $milestone->getArtifact()),
            'open'           => $status['open'],
            'closed'         => $status['closed'],
            'days_remaining' => $this->getMilestoneDaysRemaining($milestone)
        );
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

    private function buildArtifactLink(HTTPRequest $http_request, Tracker_Artifact $tracker_Artifact)
    {
        return array(
            'url'  => $http_request->getServerUrl().$tracker_Artifact->getUri(),
            'name' => $tracker_Artifact->getTracker()->getDescription().' #'.$tracker_Artifact->getId()
        );
    }

    private function buildLinkedArtifactTable(HTTPRequest $http_request, array $tracker_artifacts)
    {
        $table_body = array();

        foreach ($tracker_artifacts as $tracker_artifact) {
            $table_body[] = $this->getTrackerArtifactInfo($http_request, $tracker_artifact);
        }

        return $table_body;
    }

    private function getTrackerArtifactInfo(HTTPRequest $http_request, Tracker_Artifact $tracker_artifact)
    {
        return array(
            'artifact_link' => $this->buildArtifactLink($http_request, $tracker_artifact),
            'title'         => $tracker_artifact->getTitle(),
            'status'        => $tracker_artifact->getStatus(),
            'last_update'   => $this->getDateTime($tracker_artifact->getLastUpdateDate())
        );
    }
}