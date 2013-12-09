<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

use \Tuleap\Project\REST\ProjectReference;
use \Planning_Milestone;
use \Tuleap\Tracker\REST\Artifact\ArtifactReference;
use \Tuleap\REST\JsonCast;

/**
 * Representation of a milestone
 */
class MilestoneRepresentation {

    const ROUTE = 'milestones';

    /**
     * @var int
     */
    public $id;

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
     * @var Tuleap\REST\ResourceReference
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
     * @var float
     */
    public $capacity;

    /**
     * @var string
     */
    public $status_value;

    /**
     * @var \Tuleap\AgileDashboard\REST\v1\MilestoneParentReference | null
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
     * @var string
     */
    public $backlog_uri;

    /**
     * @var string
     */
    public $content_uri;

    /**
     * @var string Date, when the last modification occurs
     */
    public $last_modified_date;

    public function build(Planning_Milestone $milestone) {
        $this->id           = JsonCast::toInt($milestone->getArtifactId());
        $this->uri          = self::ROUTE . '/' . $this->id;
        $this->label        = $milestone->getArtifactTitle();
        $this->status_value = $milestone->getArtifact()->getStatus();
        $this->submitted_by = JsonCast::toInt($milestone->getArtifact()->getFirstChangeset()->getSubmittedBy());
        $this->submitted_on = JsonCast::toDate($milestone->getArtifact()->getFirstChangeset()->getSubmittedOn());
        $this->capacity     = JsonCast::toFloat($milestone->getCapacity());

        $this->project = new ProjectReference();
        $this->project->build($milestone->getProject());

        $this->artifact = new ArtifactReference();
        $this->artifact->build($milestone->getArtifact());

        $this->start_date = null;
        if ($milestone->getStartDate()) {
            $this->start_date = JsonCast::toDate($milestone->getStartDate());
        }

        $this->end_date = null;
        if ($milestone->getEndDate()) {
            $this->end_date = JsonCast::toDate($milestone->getEndDate());
        }

        $this->parent = null;
        $parent       = $milestone->getParent();
        if ($parent) {
            $this->parent = new MilestoneParentReference();
            $this->parent->build($parent);
        }

        $this->sub_milestones_uri = $this->uri . '/'. self::ROUTE;
        $this->backlog_uri        = $this->uri . '/'. BacklogItemRepresentation::BACKLOG_ROUTE;
        $this->content_uri        = $this->uri . '/'. BacklogItemRepresentation::CONTENT_ROUTE;
        $this->last_modified_date = JsonCast::toDate($milestone->getLastModifiedDate());
    }
}
