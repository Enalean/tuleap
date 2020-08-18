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
use Planning_Milestone;
use Tuleap\AgileDashboard\Milestone\Pane\PaneInfoCollector;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;
use Tuleap\Tracker\REST\Artifact\BurndownRepresentation;
use Tuleap\Tracker\REST\TrackerReference;

/**
 * @psalm-immutable
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
     * @var string
     */
    public $post_processed_description;

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
     * @var ProjectReference
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
     * @var int | null
     */
    public $number_days_since_start;

    /**
     * @var int | null
     */
    public $number_days_until_end;

    /**
     * @var float | null
     */
    public $capacity;

    /**
     * @var float | null
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
     * @var string | null Date, when the last modification occurs
     */
    public $last_modified_date;

    /**
     * @var array | null
     */
    public $status_count;

    /**
     * @var bool
     */
    public $has_user_priority_change_permission;

    /**
     * @var array
     */
    public $resources = [
        'milestones'       => null,
        'backlog'          => null,
        'content'          => null,
        'cardwall'         => null,
        'burndown'         => null,
        'siblings'         => null,
        'additional_panes' => [],
    ];

    private function __construct(
        int $id,
        string $uri,
        string $label,
        string $status_value,
        string $semantic_status,
        int $submitted_by,
        string $submitted_on,
        ?float $capacity,
        ?float $remaining_effort,
        ?TrackerReference $sub_milestone_type,
        PlanningReference $planning,
        ProjectReference $project,
        ArtifactReference $artifact,
        string $description,
        string $post_processed_description,
        ?string $start_date,
        ?int $number_days_since_start,
        ?string $end_date,
        ?int $number_days_until_end,
        ?MilestoneParentReference $parent,
        bool $has_user_priority_change_permission,
        ?string $last_modified_date,
        ?array $status_count,
        array $resources
    ) {
        $this->id                                  = $id;
        $this->uri                                 = $uri;
        $this->label                               = $label;
        $this->status_value                        = $status_value;
        $this->semantic_status                     = $semantic_status;
        $this->submitted_by                        = $submitted_by;
        $this->submitted_on                        = $submitted_on;
        $this->capacity                            = $capacity;
        $this->remaining_effort                    = $remaining_effort;
        $this->sub_milestone_type                  = $sub_milestone_type;
        $this->planning                            = $planning;
        $this->project                             = $project;
        $this->artifact                            = $artifact;
        $this->description                         = $description;
        $this->post_processed_description          = $post_processed_description;
        $this->start_date                          = $start_date;
        $this->number_days_since_start             = $number_days_since_start;
        $this->end_date                            = $end_date;
        $this->number_days_until_end               = $number_days_until_end;
        $this->parent                              = $parent;
        $this->has_user_priority_change_permission = $has_user_priority_change_permission;
        $this->sub_milestones_uri = $this->uri . '/' . self::ROUTE;
        $this->backlog_uri        = $this->uri . '/' . BacklogItemRepresentation::BACKLOG_ROUTE;
        $this->content_uri        = $this->uri . '/' . BacklogItemRepresentation::CONTENT_ROUTE;
        $this->last_modified_date                  = $last_modified_date;
        $this->status_count                        = $status_count;
        $this->resources                           = $resources;
    }

    public static function build(
        Planning_Milestone $milestone,
        array $status_count,
        array $backlog_trackers,
        array $parent_trackers,
        $has_user_priority_change_permission,
        $representation_type,
        ?\Planning $sub_planning,
        PaneInfoCollector $pane_info_collector,
        ?\Tracker $sub_milestone_tracker
    ): self {
        $artifact_id = $milestone->getArtifactId();
        $uri         = self::ROUTE . '/' . $artifact_id;

        $start_date              = null;
        $number_days_since_start = null;
        if ($milestone->getStartDate()) {
            $start_date = JsonCast::toDate($milestone->getStartDate());
            if ($representation_type === self::ALL_FIELDS) {
                $number_days_since_start = JsonCast::toInt($milestone->getDaysSinceStart());
            }
        }

        $end_date              = null;
        $number_days_until_end = null;
        if ($milestone->getEndDate()) {
            $end_date = JsonCast::toDate($milestone->getEndDate());
            if ($representation_type === self::ALL_FIELDS) {
                $number_days_until_end = JsonCast::toInt($milestone->getDaysUntilEnd());
            }
        }

        $parent_reference = null;
        if ($representation_type === self::ALL_FIELDS) {
            $parent = $milestone->getParent();
            if ($parent) {
                $parent_reference = MilestoneParentReference::build($parent);
            }
        }

        $status_count_ref = null;
        if ($representation_type === self::ALL_FIELDS && $status_count) {
            $status_count_ref = $status_count;
        }

        $submilestone_trackers = [];
        if ($sub_milestone_tracker) {
            $submilestone_tracker_ref = TrackerReference::build($sub_milestone_tracker);
            $submilestone_trackers = [$submilestone_tracker_ref];
        }

        $resources = [];

        $resources['milestones'] = [
            'uri'    => $uri . '/' . self::ROUTE,
            'accept' => [
                'trackers' => $submilestone_trackers
            ]
        ];
        $resources['backlog'] = [
            'uri'    => $uri . '/' . BacklogItemRepresentation::BACKLOG_ROUTE,
            'accept' => [
                'trackers'        => self::getTrackersRepresentation($backlog_trackers),
                'parent_trackers' => self::getTrackersRepresentation($parent_trackers)
            ]
        ];
        $resources['content'] = [
            'uri'    => $uri . '/' . BacklogItemRepresentation::CONTENT_ROUTE,
            'accept' => [
                'trackers' => self::getContentTrackersRepresentation($milestone)
            ]
        ];
        $resources['siblings'] = [
            'uri' => $uri . '/siblings'
        ];
        $resources['cardwall'] = null;
        $resources['burndown'] = null;

        $resources['additional_panes'] = [];
        foreach ($pane_info_collector->getPanes() as $pane_info) {
            $representation = new PaneInfoRepresentation();
            $representation->build($pane_info);
            $resources['additional_panes'][] = $representation;
        }

        return new self(
            JsonCast::toInt($artifact_id),
            $uri,
            $milestone->getArtifactTitle() ?? '',
            $milestone->getArtifact()->getStatus(),
            $milestone->getArtifact()->getSemanticStatusValue(),
            JsonCast::toInt($milestone->getArtifact()->getFirstChangeset()->getSubmittedBy()),
            JsonCast::toDate($milestone->getArtifact()->getFirstChangeset()->getSubmittedOn()),
            JsonCast::toFloat($milestone->getCapacity()),
            JsonCast::toFloat($milestone->getRemainingEffort()),
            self::getSubmilestoneType($sub_planning),
            new PlanningReference($milestone->getPlanning()),
            new ProjectReference($milestone->getProject()),
            ArtifactReference::build($milestone->getArtifact()),
            $milestone->getArtifact()->getDescription(),
            $milestone->getArtifact()->getPostProcessedDescription(),
            $start_date,
            $number_days_since_start,
            $end_date,
            $number_days_until_end,
            $parent_reference,
            $has_user_priority_change_permission,
            JsonCast::toDate($milestone->getLastModifiedDate()),
            $status_count_ref,
            $resources
        );
    }

    public static function buildWithBurndownEnabled(self $representation): self
    {
        $representation_with_burndown = clone $representation;

        $representation_with_burndown->burndown_uri = $representation_with_burndown->uri . '/' . BurndownRepresentation::ROUTE;
        $representation_with_burndown->resources['burndown'] = [
            'uri' => $representation_with_burndown->burndown_uri
        ];

        return $representation_with_burndown;
    }

    public static function buildWithCardwallEnabled(self $representation): self
    {
        $representation_with_cardwall = clone $representation;

        $representation_with_cardwall->cardwall_uri = $representation_with_cardwall->uri . '/' . AgileDashboard_MilestonesCardwallRepresentation::ROUTE;
        $representation_with_cardwall->resources['cardwall'] = [
            'uri' => $representation_with_cardwall->cardwall_uri
        ];

        return $representation_with_cardwall;
    }

    private static function getContentTrackersRepresentation(Planning_Milestone $milestone)
    {
        return self::getTrackersRepresentation(
            $milestone->getPlanning()->getBacklogTrackers()
        );
    }

    private static function getTrackersRepresentation(array $trackers)
    {
        $trackers_representation = [];
        foreach ($trackers as $tracker) {
            $tracker_reference = TrackerReference::build($tracker);
            $trackers_representation[] = $tracker_reference;
        }
        return $trackers_representation;
    }

    private static function getSubmilestoneType(?\Planning $planning): ?TrackerReference
    {
        $submilestone_type = null;

        if ($planning) {
            $tracker_reference = TrackerReference::build($planning->getPlanningTracker());

            $submilestone_type = $tracker_reference;
        }

        return $submilestone_type;
    }
}
