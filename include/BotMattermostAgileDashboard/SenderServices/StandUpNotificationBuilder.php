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

use BaseLanguage;
use ForgeConfig;
use PFUser;
use PlanningFactory;
use Planning;
use Planning_MilestoneFactory;
use Planning_Milestone;
use AgileDashboard_Milestone_MilestoneStatusCounter;
use Project;
use Tracker_Artifact;


class StandUpNotificationBuilder
{
    private $milestone_factory;
    private $milestone_status_counter;
    private $markdown_formatter;
    private $planning_factory;
    private $language;

    public function __construct(
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_Milestone_MilestoneStatusCounter $milestone_status_counter,
        MarkdownFormatter $markdown_formatter,
        PlanningFactory $planning_factory,
        BaseLanguage $language
    ) {
        $this->milestone_factory        = $milestone_factory;
        $this->milestone_status_counter = $milestone_status_counter;
        $this->markdown_formatter       = $markdown_formatter;
        $this->planning_factory         = $planning_factory;
        $this->language                 = $language;
    }

    public function buildNotificationText(PFUser $user, Project $project)
    {
        $last_plannings = $this->planning_factory->getLastLevelPlannings($user, $project->getID());
        $text           = '';

        if (! empty($last_plannings)) {
            foreach ($last_plannings as $last_planning) {
                $text .= $this->markdown_formatter->addLineOfText(
                    $this->buildPlanningNotificationText($last_planning, $user, $project->getPublicName())
                );
            }
        } else {
            $text .= $this->language->getText(
                'plugin_botmattermost_agiledashboard',
                'notification_builder_no_current_plannings',
                array($project->getPublicName())
            );
        }

        return $text;
    }

    private function buildPlanningNotificationText(Planning $last_planning, PFUser $user, $project_name)
    {
        $milestones = $this->milestone_factory->getAllCurrentMilestones($user, $last_planning);

        if (! empty($milestones)) {
            $title = $this->language->getText(
                'plugin_botmattermost_agiledashboard',
                'notification_builder_title_stand_up_summary',
                array($last_planning->getName(), $project_name)
            );
            $text  = $this->markdown_formatter->addTitleOfLevel($title, 3);

            foreach ($milestones as $milestone) {
                $milestone = $this->milestone_factory->updateMilestoneContextualInfo($user, $milestone);

                $text .= $this->markdown_formatter->addSeparationLine();
                $text .= $this->markdown_formatter->addLineOfText(
                    $this->buildMilestoneNotificationText($milestone, $user)
                );
            }
        } else {
            $text = $this->language->getText(
                'plugin_botmattermost_agiledashboard',
                'notification_builder_no_current_milestones',
                array($last_planning->getName(), $project_name)
            );
        }

        return $text;
    }

    private function buildMilestoneNotificationText(Planning_Milestone $milestone, PFUser $user)
    {
        $milestone_table = $this->markdown_formatter->createSimpleTableText(
            $this->getMilestoneInformation($milestone, $user)
        );
        $link            = $this->language->getText(
                'plugin_botmattermost_agiledashboard', 'notification_builder_quick_access'
            ).' : '.$this->getPlanningCardwallLink($milestone);

        $text = $this->markdown_formatter->addTitleOfLevel(
            $milestone->getArtifactTitle().' '.$this->buildMilestoneDatesInfo($milestone), 4
        );
        $text .= $this->markdown_formatter->addLineOfText($link);
        $text .= $this->markdown_formatter->addLineOfText('');
        $text .= $this->markdown_formatter->addLineOfText($milestone_table);
        $text .= $this->buildLinkedArtifactsNotificationTextByMilestone($milestone, $user);

        $text .= $this->markdown_formatter->addLineOfText(
            $this->markdown_formatter->createImage(
                'Burndown',
                $this->getBurndownImageUrl($milestone->getArtifact(), $user)
            )
        );

        return $text;
    }

    private function getBurndownImageUrl(Tracker_Artifact $artifact, $user)
    {
        $url_query = http_build_query(
            array(
                'formElement' => $artifact->getABurndownField($user)->getId(),
                'func'        => \Tracker_FormElement_Field_Burndown::FUNC_SHOW_BURNDOWN,
                'src_aid'     => $artifact->getId()
            )
        );

        return $this->getPrefixUrl().TRACKER_BASE_URL.'/?'.$url_query;
    }

    private function buildLinkedArtifactsNotificationTextByMilestone(Planning_Milestone $milestone, PFUser $user)
    {
        $linked_artifacts = $this->getLinkedArtifactsWithRecentModification($milestone, $user);
        $text             = '';

        if (! empty($linked_artifacts)) {
            $artifacts_table = $this->buildLinkedArtifactTable($linked_artifacts);
            $text .= $this->markdown_formatter->addLineOfText($artifacts_table);
        } else {
            $text .= $this->markdown_formatter->addTitleOfLevel(
                $this->language->getText('plugin_botmattermost_agiledashboard', 'notification_builder_no_update').
                ' '.$milestone->getArtifactTitle(),
                5
            );
        }

        return $text;
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

    private function getPlanningCardwallLink(Planning_Milestone $milestone)
    {
        return $this->buildMilestoneLinkForPane($milestone, "cardwall", "Card Wall");
    }

    private function checkModificationOnArtifact(Tracker_Artifact $artifact)
    {
        return $artifact->getLastUpdateDate() > strtotime('-1 day', time());
    }

    private function getMilestoneInformation(Planning_Milestone $milestone, PFUser $user)
    {
        $status           = $this->milestone_status_counter->getStatus($user, $milestone->getArtifactId());
        $milestone_infos  = array(
            $this->language->getText('plugin_botmattermost_agiledashboard', 'notification_builder_artifact_id')
            => $this->buildArtifactLink($milestone->getArtifact()),
            $this->language->getText('plugin_botmattermost_agiledashboard', 'notification_builder_status_open')
            => $status['open'],
            $this->language->getText('plugin_botmattermost_agiledashboard', 'notification_builder_status_closed')
            => $status['closed'],
            $this->language->getText('plugin_botmattermost_agiledashboard', 'notification_builder_days_remaining')
            => $this->getMilestoneDaysRemaining($milestone)
        );
        $remaining_effort = $milestone->getRemainingEffort();

        if (isset($remaining_effort)) {
            $milestone_infos[$this->language->getText(
                'plugin_botmattermost_agiledashboard', 'notification_builder_remaining_effort'
            )] = $remaining_effort;
        }

        return $milestone_infos;
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

    private function buildArtifactLink(Tracker_Artifact $tracker_Artifact)
    {
        $url_artifact = $this->getPrefixUrl().$tracker_Artifact->getUri();
        $link_name    = $tracker_Artifact->getTracker()->getDescription().' #'.$tracker_Artifact->getId();

        return $this->markdown_formatter->createLink($link_name, $url_artifact);
    }

    private function buildMilestoneLinkForPane(Planning_Milestone $milestone, $pane_name, $link_name)
    {
        $url = $this->getPrefixUrl().AGILEDASHBOARD_BASE_URL.'/?'.http_build_query(array(
            'group_id'    => $milestone->getGroupId(),
            'planning_id' => $milestone->getPlanningId(),
            'action'      => 'show',
            'aid'         => $milestone->getArtifactId(),
            'pane'        => $pane_name
        ));

        return $this->markdown_formatter->createLink($link_name, $url);
    }

    private function getPrefixUrl()
    {
        if (ForgeConfig::get('sys_force_ssl')) {
            $prefix_url = 'https://'.ForgeConfig::get('sys_https_host');
        } else {
            $prefix_url = 'http://'.ForgeConfig::get('sys_default_domain');
        }

        return $prefix_url;
    }

    private function buildMilestoneDatesInfo(Planning_Milestone $milestone)
    {
        return '_'.$this->getDate($milestone->getStartDate()).' - '.$this->getDate($milestone->getEndDate()).'_';
    }

    private function buildLinkedArtifactTable(array $tracker_artifacts)
    {
        $table_body   = array();
        $table_header = array(
            $this->language->getText('plugin_botmattermost_agiledashboard', 'notification_builder_artifact_id'),
            $this->language->getText('plugin_botmattermost_agiledashboard', 'notification_builder_artifact_title'),
            $this->language->getText(
                'plugin_botmattermost_agiledashboard', 'notification_builder_artifact_status'
            ),
            $this->language->getText(
                'plugin_botmattermost_agiledashboard', 'notification_builder_artifact_last_modification'
            )
        );

        foreach ($tracker_artifacts as $tracker_artifact) {
            $table_body[] = $this->getTrackerArtifactInfo($tracker_artifact);
        }

        return $this->markdown_formatter->createTableText($table_header, $table_body);
    }

    private function getTrackerArtifactInfo(Tracker_Artifact $tracker_artifact)
    {
        return array(
            $this->buildArtifactLink($tracker_artifact),
            $tracker_artifact->getTitle(),
            $tracker_artifact->getStatus(),
            $this->getDateTime($tracker_artifact->getLastUpdateDate()),
        );
    }
}