<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v1;

use AgileDashboard_MilestonesCardwallRepresentation;
use EventManager;
use Planning_Milestone;
use PlanningFactory;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;
use Tuleap\Tracker\REST\Artifact\BurndownRepresentation;
use Tuleap\Tracker\REST\TrackerReference;

/**
 * Representation of a milestone
 */
class MilestoneRepresentation
{
    public const ROUTE      = 'milestones';
    public const ALL_FIELDS = 'all';
    public const SLIM       = 'slim';

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $description;

    /**
     * @var String
     */
    public $uri;

    /**
     * @var String
     */
    public $label;

    /**
     * @var int
     */
    public $submitted_by;

    /**
     * @var String
     */
    public $submitted_on;

    /**
     * @var \Tuleap\REST\v1\PlanningReferenceBase
     */
    public $planning;

    /**
     * @var \Tuleap\REST\ResourceReference
     */
    public $project;

    /**
     * @var string | null
     */
    public $start_date;

    /**
     * @var string | null
     */
    public $end_date;

    /**
     * @var int
     */
    public $number_days_since_start;

    /**
     * @var int
     */
    public $number_days_until_end;

    /**
     * @var float
     */
    public $capacity;

    /**
     * @var float
     */
    public $remaining_effort;

    /**
     * @var string
     */
    public $status_value;

    /**
     * @var string
     */
    public $semantic_status;

    /**
     * @var MilestoneParentReference | null
     */
    public $parent;

    /**
     * @var \Tuleap\Tracker\REST\Artifact\ArtifactReference
     */
    public $artifact;

    /**
     * @var string
     */
    public $sub_milestones_uri;

    /**
     * @var \Tuleap\Tracker\REST\TrackerReference | null
     */
    public $sub_milestone_type;

    /**
     * @var string
     */
    public $backlog_uri;

    /**
     * @var string
     */
    public $content_uri;

    /**
     * @var string
     */
    public $cardwall_uri = null;

    /**
     * @var string
     */
    public $burndown_uri = null;

    /**
     * @var string Date, when the last modification occurs
     */
    public $last_modified_date;

    /**
     * @var array
     */
    public $status_count;

    /**
     * @var bool
     */
    public $has_user_priority_change_permission;

    /**
     * @var array
     */
    public $resources = array(
        'milestones'       => null,
        'backlog'          => null,
        'content'          => null,
        'cardwall'         => null,
        'burndown'         => null,
        'siblings'         => null,
        'additional_panes' => [],
    );

    public function build(
        Planning_Milestone $milestone,
        array $status_count,
        array $backlog_trackers,
        array $parent_trackers,
        $has_user_priority_change_permission,
        $representation_type,
        $is_mono_milestone_enabled
    ) {
        $this->id                   = JsonCast::toInt($milestone->getArtifactId());
        $this->uri                  = self::ROUTE . '/' . $this->id;
        $this->label                = $milestone->getArtifactTitle() ?? '';
        $this->status_value         = $milestone->getArtifact()->getStatus();
        $this->semantic_status      = $milestone->getArtifact()->getSemanticStatusValue();
        $this->submitted_by         = JsonCast::toInt($milestone->getArtifact()->getFirstChangeset()->getSubmittedBy());
        $this->submitted_on         = JsonCast::toDate($milestone->getArtifact()->getFirstChangeset()->getSubmittedOn());
        $this->capacity             = JsonCast::toFloat($milestone->getCapacity());
        $this->remaining_effort     = JsonCast::toFloat($milestone->getRemainingEffort());
        $this->sub_milestone_type   = $this->getSubmilestoneType($milestone, $is_mono_milestone_enabled);

        $this->planning = new PlanningReference();
        $this->planning->build($milestone->getPlanning());

        $this->project = new ProjectReference();
        $this->project->build($milestone->getProject());

        $this->artifact = new ArtifactReference();
        $this->artifact->build($milestone->getArtifact());

        $this->description = (string) $milestone->getArtifact()->getDescription();

        $this->start_date = null;
        if ($milestone->getStartDate()) {
            $this->start_date              = JsonCast::toDate($milestone->getStartDate());
            if ($representation_type === self::ALL_FIELDS) {
                $this->number_days_since_start = JsonCast::toInt($milestone->getDaysSinceStart());
            }
        }

        $this->end_date = null;
        if ($milestone->getEndDate()) {
            $this->end_date              = JsonCast::toDate($milestone->getEndDate());
            if ($representation_type === self::ALL_FIELDS) {
                $this->number_days_until_end = JsonCast::toInt($milestone->getDaysUntilEnd());
            }
        }

        if ($representation_type === self::ALL_FIELDS) {
            $this->parent = null;
            $parent       = $milestone->getParent();
            if ($parent) {
                $this->parent = new MilestoneParentReference();
                $this->parent->build($parent);
            }
        }

        $this->has_user_priority_change_permission = $has_user_priority_change_permission;

        $this->sub_milestones_uri = $this->uri . '/' . self::ROUTE;
        $this->backlog_uri        = $this->uri . '/' . BacklogItemRepresentation::BACKLOG_ROUTE;
        $this->content_uri        = $this->uri . '/' . BacklogItemRepresentation::CONTENT_ROUTE;
        $this->last_modified_date = JsonCast::toDate($milestone->getLastModifiedDate());
        if ($representation_type === self::ALL_FIELDS && $status_count) {
            $this->status_count = $status_count;
        }

        $finder = new \AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder(
            \Tracker_HierarchyFactory::instance(),
            PlanningFactory::build(),
            new ScrumForMonoMilestoneChecker(new ScrumForMonoMilestoneDao(), PlanningFactory::build())
        );
        $submilestone_tracker = $finder->findFirstSubmilestoneTracker($milestone);

        $submilestone_trackers = array();
        if ($submilestone_tracker) {
            $submilestone_tracker_ref = new TrackerReference();
            $submilestone_tracker_ref->build($finder->findFirstSubmilestoneTracker($milestone));
            $submilestone_trackers = array($submilestone_tracker_ref);
        }

        $this->resources['milestones'] = array(
            'uri'    => $this->uri . '/' . self::ROUTE,
            'accept' => array(
                'trackers' => $submilestone_trackers
            )
        );
        $this->resources['backlog'] = array(
            'uri'    => $this->uri . '/' . BacklogItemRepresentation::BACKLOG_ROUTE,
            'accept' => array(
                'trackers'        => $this->getTrackersRepresentation($backlog_trackers),
                'parent_trackers' => $this->getTrackersRepresentation($parent_trackers)
            )
        );
        $this->resources['content'] = array(
            'uri'    => $this->uri . '/' . BacklogItemRepresentation::CONTENT_ROUTE,
            'accept' => array(
                'trackers' => $this->getContentTrackersRepresentation($milestone)
            )
        );
        $this->resources['siblings'] = [
            'uri' => $this->uri . '/siblings'
        ];

        $event = new AdditionalPanesForMilestoneEvent($milestone);
        EventManager::instance()->processEvent($event);

        $this->resources['additional_panes'] = $event->getPaneInfoRepresentations();
    }

    private function getContentTrackersRepresentation(Planning_Milestone $milestone)
    {
        return $this->getTrackersRepresentation(
            $milestone->getPlanning()->getBacklogTrackers()
        );
    }

    private function getTrackersRepresentation(array $trackers)
    {
        $trackers_representation = array();
        foreach ($trackers as $tracker) {
            $tracker_reference = new TrackerReference();
            $tracker_reference->build($tracker);
            $trackers_representation[] = $tracker_reference;
        }
        return $trackers_representation;
    }

    public function enableCardwall()
    {
        $this->cardwall_uri = $this->uri . '/' . AgileDashboard_MilestonesCardwallRepresentation::ROUTE;
        $this->resources['cardwall'] = array(
            'uri' => $this->cardwall_uri
        );
    }

    public function enableBurndown()
    {
        $this->burndown_uri = $this->uri . '/' . BurndownRepresentation::ROUTE;
        $this->resources['burndown'] = array(
            'uri' => $this->burndown_uri
        );
    }

    private function getSubmilestoneType(Planning_Milestone $milestone, $is_mono_milestone_enabled)
    {
        $submilestone_type = null;

        if ($is_mono_milestone_enabled === true) {
            $planning = $this->getPlanning($milestone);
        } else {
            $planning = $this->getChildrenPlanning($milestone);
        }

        if ($planning) {
            $tracker_reference = new TrackerReference();
            $tracker_reference->build($planning->getPlanningTracker());

            $submilestone_type = $tracker_reference;
        }

        return $submilestone_type;
    }

    private function getChildrenPlanning(Planning_Milestone $milestone)
    {
        return PlanningFactory::build()->getChildrenPlanning($milestone->getPlanning());
    }

    private function getPlanning(Planning_Milestone $milestone)
    {
        return PlanningFactory::build()->getPlanning($milestone->getPlanning()->getId());
    }
}
