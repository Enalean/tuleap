<?php
/**
 * Copyright Enalean (c) 2013 - 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\AgileDashboard\FormElement\BurnupFieldRetriever;
use Tuleap\AgileDashboard\Milestone\Pane\Conent\ContentChartPresenter;

class AgileDashboard_Milestone_Pane_Content_ContentPresenterBuilder
{
    /** @var AgileDashboard_Milestone_Backlog_BacklogFactory */
    private $backlog_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory */
    private $collection_factory;

    /**
     * @var BurnupFieldRetriever
     */
    private $field_retriever;

    public function __construct(
        AgileDashboard_Milestone_Backlog_BacklogFactory $backlog_factory,
        AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory $collection_factory,
        BurnupFieldRetriever $field_retriever
    ) {
        $this->backlog_factory    = $backlog_factory;
        $this->collection_factory = $collection_factory;
        $this->field_retriever    = $field_retriever;
    }

    public function getMilestoneContentPresenter(PFUser $user, Planning_Milestone $milestone)
    {
        $redirect_parameter = new Planning_MilestoneRedirectParameter();
        $backlog            = $this->backlog_factory->getBacklog($milestone);
        $redirect_to_self   = $redirect_parameter->getPlanningRedirectToSelf($milestone, AgileDashboard_Milestone_Pane_Content_ContentPaneInfo::IDENTIFIER);

        $descendant_trackers = $backlog->getDescendantTrackers();

        $chart_presenter = $this->getChartPresenter($milestone, $user);

        return new AgileDashboard_Milestone_Pane_Content_ContentPresenter(
            $milestone,
            $this->collection_factory->getOpenClosedAndInconsistentCollection($user, $milestone, $backlog, $redirect_to_self),
            $this->collection_factory->getInconsistentCollection($user, $milestone, $backlog, $redirect_to_self),
            $descendant_trackers,
            $this->getSolveInconsistenciesUrl($milestone, $redirect_to_self),
            $user,
            $chart_presenter
        );
    }

    private function getChartPresenter(Planning_Milestone $milestone, PFUser $user)
    {
        $artifact = $milestone->getArtifact();


        $burndown_field = $artifact->getABurndownField($user);
        $has_burndown   = false;
        $burndown_label = null;
        $burndown_url   = null;
        if ($burndown_field) {
            $has_burndown   = true;
            $burndown_label = $burndown_field->getLabel();
            $burndown_url   = $burndown_field->getBurndownImageUrl($artifact);
        }

        $has_burnup       = false;
        $burnup_label     = null;
        $burnup_presenter = null;
        $burnup_field     = $this->field_retriever->getField($milestone->getArtifact(), $user);
        if ($burnup_field) {
            $has_burnup       = true;
            $burnup_label     = $burnup_field->getLabel();
            $burnup_presenter = $burnup_field->buildPresenter($milestone->getArtifact(), false, $user);
        }

        return new ContentChartPresenter(
            $has_burndown,
            $burndown_label,
            $burndown_url,
            $has_burnup,
            $burnup_label,
            $burnup_presenter
        );
    }

    private function getSolveInconsistenciesUrl(Planning_Milestone $milestone, $redirect_to_self)
    {
        return  AGILEDASHBOARD_BASE_URL.
            "/?group_id=".$milestone->getGroupId().
            "&aid=".$milestone->getArtifactId().
            "&action=solve-inconsistencies".
            "&".$redirect_to_self;
    }
}
