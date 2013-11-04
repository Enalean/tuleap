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

/**
 * Representation of a milestone
 */
class MilestoneRepresentation {

    const ROUTE = 'milestones';

    /** @var int */
    public $id;

    /** @var int */
    public $artifact_id;

    /** @var string */
    public $reference_uri;

    /** @var String */
    public $label;

    /** @var int */
    public $submitted_by;

    /** @var String */
    public $submitted_on;

    /** @var String */
    public $uri;

    /** @var int */
    public $project_id;

    /** @var String */
    public $start_date;

    /** @var String */
    public $end_date;

    /** @var float */
    public $capacity;

    /** @var MilestoneInfoRepresentation|null */
    public $parent;

    /** @var MilestoneInfoRepresentation[] */
    public $sub_milestones = array();

    public function __construct(\Planning_Milestone $milestone, array $sub_milestones) {
        $this->id                 = $milestone->getArtifactId();
        $this->artifact_id        = $milestone->getArtifactId();
        $this->reference_uri      = $milestone->getArtifact()->getRestUri();
        $this->uri                = self::ROUTE . '/' . $this->id;
        $this->label              = $milestone->getArtifactTitle();
        $this->submitted_by       = $milestone->getArtifact()->getFirstChangeset()->getSubmittedBy();
        $this->submitted_on       = date('c', $milestone->getArtifact()->getFirstChangeset()->getSubmittedOn());
        $this->project_id         = $milestone->getProject()->getID();
        if ($milestone->getStartDate()) {
            $this->start_date         = date('c', $milestone->getStartDate());
        }
        if ($milestone->getEndDate()) {
            $this->end_date           = date('c', $milestone->getEndDate());
        }
        $this->capacity           = $milestone->getCapacity();
        $parent                   = $milestone->getParent();
        if ($parent) {
            $this->parent         = new MilestoneInfoRepresentation($parent);
        }

        foreach ($sub_milestones as $sub_milestone) {
            $this->sub_milestones[] = new MilestoneInfoRepresentation($sub_milestone);
        }
    }
}
