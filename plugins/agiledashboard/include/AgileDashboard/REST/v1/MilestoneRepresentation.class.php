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

use \Rest_ResourceReference;
use \Tuleap\Project\REST\v1\ProjectResource;
use \Planning_Milestone;
use \Tracker_REST_Artifact_ArtifactRepresentation;

/**
 * Representation of a milestone
 */
class MilestoneRepresentation {

    const ROUTE = 'milestones';

    /** @var int */
    public $id;

    /** @var String */
    public $uri;

    /** @var String */
    public $label;

    /** @var int */
    public $submitted_by;

    /** @var String */
    public $submitted_on;

    /** @var Rest_ResourceReference */
    public $project;

    /** @var String */
    public $start_date;

    /** @var String */
    public $end_date;

    /** @var float */
    public $capacity;

    /** @var Rest_ResourceReference|null */
    public $parent;

    /** @var Rest_ResourceReference */
    public $artifact;

    /** @var string */
    public $sub_milestones_uri;

    /** @var string*/
    public $backlog_items_uri;

    public function __construct(Planning_Milestone $milestone) {
        $this->id                 = $milestone->getArtifactId();
        $this->uri                = self::ROUTE . '/' . $this->id;
        $this->label              = $milestone->getArtifactTitle();
        $this->submitted_by       = $milestone->getArtifact()->getFirstChangeset()->getSubmittedBy();
        $this->submitted_on       = date('c', $milestone->getArtifact()->getFirstChangeset()->getSubmittedOn());
        $this->capacity           = $milestone->getCapacity();
        $this->project            = new Rest_ResourceReference($milestone->getProject()->getID(), ProjectResource::ROUTE);
        $this->artifact           = new Rest_ResourceReference($milestone->getArtifactId(), Tracker_REST_Artifact_ArtifactRepresentation::ROUTE);
        if ($milestone->getStartDate()) {
            $this->start_date         = date('c', $milestone->getStartDate());
        }
        if ($milestone->getEndDate()) {
            $this->end_date           = date('c', $milestone->getEndDate());
        }
        $parent                   = $milestone->getParent();
        if ($parent) {
            $this->parent = new Rest_ResourceReference($parent->getArtifactId(), self::ROUTE);
        }
        $this->sub_milestones_uri = $this->uri . '/'. self::ROUTE;
        $this->backlog_items_uri  = $this->uri . '/'. BacklogItemRepresentation::ROUTE;

    }
}
