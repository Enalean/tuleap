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

class AgileDashboard_Milestone_Backlog_BacklogItem implements AgileDashboard_Milestone_Backlog_IBacklogItem
{
    /** @var Int */
    private $id;

    /** @var String */
    private $title;

    /** @var String */
    private $type;

    /** @var String */
    private $short_type;

    /** @var Int */
    private $initial_effort;

    /** @var float */
    private $remaining_effort;

    /** @var String */
    private $status;

    /**
     * @var String
     */
    private $normalized_status_label;

    /** @var String */
    private $color;

    /** @var Tracker_Artifact */
    private $artifact;

    /** @var Tracker_Artifact */
    private $parent;

    /** @var bool */
    private $has_children = null;
    /**
     * @var
     */
    private $is_inconsistent;

    public function __construct(Tracker_Artifact $artifact, $is_inconsistent)
    {
        $this->id              = $artifact->getId();
        $this->title           = $artifact->getTitle();
        $this->artifact        = $artifact;
        $this->color           = $this->artifact->getTracker()->getColor()->getName();
        $this->type            = $this->artifact->getTracker()->getName();
        $this->short_type      = $this->artifact->getTracker()->getItemName();
        $this->is_inconsistent = $is_inconsistent;
    }

    public function setParent(Tracker_Artifact $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return Tracker_Artifact
     */
    public function getParent()
    {
        return $this->parent;
    }

    public function setInitialEffort($value)
    {
        $this->initial_effort = $value;
    }

    public function getInitialEffort()
    {
        return $this->initial_effort;
    }

    public function setStatus($status, $status_semantic)
    {
        $this->status                  = $status;
        $this->normalized_status_label = $status_semantic;
    }

    public function id()
    {
        return $this->id;
    }

    public function title()
    {
        return $this->title;
    }

    public function type()
    {
        return $this->type;
    }

    public function short_type()
    {
        return $this->short_type;
    }

    public function points()
    {
        return $this->initial_effort;
    }

    public function parent_title()
    {
        if ($this->parent) {
            return $this->parent->getTitle();
        }
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function color()
    {
        return $this->color;
    }

    public function setHasChildren($has_children)
    {
        $this->has_children = $has_children;
    }

    public function hasChildren()
    {
        if ($this->has_children === null) {
            return $this->artifact->hasChildren();
        }
        return $this->has_children;
    }
    /**
     * @return Tracker_Artifact
     */
    public function getArtifact()
    {
        return $this->artifact;
    }

    public function xRef()
    {
        return $this->artifact->getXRef();
    }

    /**
     * @return bool
     */
    public function isInconsistent()
    {
        return $this->is_inconsistent;
    }

    public function getNormalizedStatusLabel()
    {
        return $this->normalized_status_label;
    }

    public function isOpen()
    {
        return $this->artifact->isOpen();
    }

    public function getRemainingEffort()
    {
        return $this->remaining_effort;
    }

    public function setRemainingEffort($value)
    {
        $this->remaining_effort = $value;
    }
}
