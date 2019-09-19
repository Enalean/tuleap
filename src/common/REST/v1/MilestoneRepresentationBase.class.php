<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

namespace Tuleap\REST\v1;

/**
 * Representation of a milestone
 */
class MilestoneRepresentationBase
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
     * @var String
     */
    public $start_date;

    /**
     * @var String
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
     * @var \Tuleap\REST\v1\MilestoneParentReferenceBase | null
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
    * @var @type \Tuleap\Tracker\REST\TrackerRepresentation
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
}
