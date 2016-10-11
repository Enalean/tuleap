<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use ForgeConfig;
use PFUser;
use PlanningFactory;
use Planning;
use Planning_MilestoneFactory;
use Planning_Milestone;
use AgileDashboard_Milestone_MilestoneStatusCounter;


class StandUpNotificationBuilder
{
    private $milestone_factory;
    private $milestone_status_counter;
    private $markdown_formatter;
    private $planning_factory;

    public function __construct(
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_Milestone_MilestoneStatusCounter $milestone_status_counter,
        MarkdownFormatter $markdown_formatter,
        PlanningFactory $planning_factory
    ) {
        $this->milestone_factory        = $milestone_factory;
        $this->milestone_status_counter = $milestone_status_counter;
        $this->markdown_formatter       = $markdown_formatter;
        $this->planning_factory         = $planning_factory;
    }

    public function buildNotificationText(PFUser $user, $project_id)
    {
        $last_plannings = $this->planning_factory->getLastLevelPlannings($user, $project_id);

        $text = '';

        foreach ($last_plannings as $last_planning) {
            $text .= $this->markdown_formatter->addLineOfText(
                $this->buildPlanningNotificationText($last_planning, $user)
            );
        }

        return $text;
    }

    private function buildPlanningNotificationText(Planning $last_planning, PFUser $user)
    {
        $milestones = $this->milestone_factory->getAllCurrentMilestones($user, $last_planning);
        if (! empty($milestones)) {
            $text = $this->markdown_formatter->addTitleOfLevel(
                $GLOBALS['Language']->getText(
                    'plugin_botmattermost_agiledashboard', 'notification_builder_title_stand_up_summary'
                ).
                ' '.$last_planning->getPlanningTracker()->getName()
                , 4
            );
            foreach ($milestones as $milestone) {
                $milestone = $this->milestone_factory->updateMilestoneContextualInfo(
                    $user, $milestone
                );
                $text .= $this->markdown_formatter->addLineOfText(
                    $this->buildMilestoneNotificationText($milestone, $user)
                );
            }
        } else {
            $text = $GLOBALS['Language']->getText(
                'plugin_botmattermost_agiledashboard', 'notification_builder_no_current_milestone'
            );
        }

        return $text;
    }

    private function buildMilestoneNotificationText(Planning_Milestone $milestone, PFUser $user)
    {
        $status          = $this->milestone_status_counter->getStatus($user, $milestone->getArtifactId());
        $milestone_infos = array(
            $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'notification_builder_artifact_id')
            => $this->buildArtifactLink($milestone),
            $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'notification_builder_status_open')
            => $status['open'],
            $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'notification_builder_status_closed')
            => $status['closed'],
            $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'notification_builder_days_remaining')
            => $this->getMilestoneDaysRemaining($milestone)
        );

        $table = $this->markdown_formatter->createTableText($milestone_infos);
        $text  = $this->markdown_formatter->addTitleOfLevel(
            $milestone->getArtifactTitle().' '.$this->buildMilestoneDatesInfo($milestone), 4
        );
        $text .= $this->markdown_formatter->addLineOfText($table);

        return $text;
    }

    private function getMilestoneStartDate(Planning_Milestone $milestone)
    {
        return date('d M', $milestone->getStartDate());
    }

    private function getMilestoneEndDate(Planning_Milestone $milestone)
    {
        return date('d M', $milestone->getEndDate());
    }

    private function getMilestoneDaysRemaining(Planning_Milestone $milestone)
    {
        return max($milestone->getDaysUntilEnd(), 0);
    }

    private function buildArtifactLink(Planning_Milestone $milestone)
    {
        $url_artifact = ForgeConfig::get('sys_https_host').$milestone->getArtifact()->getUri();
        $link_name = 'Sprint #'.$milestone->getArtifactId();

        return "[$link_name]($url_artifact)";
    }

    private function buildMilestoneDatesInfo(Planning_Milestone $milestone)
    {
        return '_'.$this->getMilestoneStartDate($milestone).' - '.$this->getMilestoneEndDate($milestone).'_';
    }
}