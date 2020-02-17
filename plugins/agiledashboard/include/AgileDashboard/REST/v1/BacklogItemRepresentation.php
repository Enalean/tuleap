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

use Tuleap\Cardwall\BackgroundColor\BackgroundColor;
use Tuleap\REST\JsonCast;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;
use Tuleap\Tracker\REST\TrackerReference;

class BacklogItemRepresentation
{
    public const BACKLOG_ROUTE = 'backlog';

    public const CONTENT_ROUTE = 'content';

    public const ROUTE         = 'backlog_items';

    /**
     * @var Int
     */
    public $id;

    /**
     * @var String
     */
    public $label;

    /**
     * @var String
     */
    public $type;

    /**
     * @var String
     */
    public $short_type;

    /**
     * @var String
     */
    public $status;

    /**
     * @var String
     */
    public $color;

    /**
     * @var String
     */
    public $background_color_name;

    /**
     * @var Float
     */
    public $initial_effort;

    /** @var float */
    public $remaining_effort;

    /**
     * @var \Tuleap\Tracker\REST\Artifact\ArtifactReference
     */
    public $artifact;

    /**
     * @var BacklogItemParentReference
     */
    public $parent;

    /**
     * @var \Tuleap\Project\REST\ProjectReference
     */
    public $project;

    /**
     * @var bool
     */
    public $has_children;

    /**
     * @var array
     */
    public $accept;

    /**
     * @var array
     */
    public $card_fields;

    public function build(
        \AgileDashboard_Milestone_Backlog_IBacklogItem $backlog_item,
        array $card_fields,
        BackgroundColor $background_color
    ) {
        $this->id               = JsonCast::toInt($backlog_item->id());
        $this->label            = $backlog_item->title();
        $this->status           = $backlog_item->getNormalizedStatusLabel();
        $this->type             = $backlog_item->type();
        $this->short_type       = $backlog_item->short_type();
        $this->initial_effort   = JsonCast::toFloat($backlog_item->getInitialEffort());
        $this->remaining_effort = JsonCast::toFloat($backlog_item->getRemainingEffort());
        $this->color            = $backlog_item->color();

        $this->artifact = new ArtifactReference();
        $this->artifact->build($backlog_item->getArtifact());

        $this->project = new ProjectReference();
        $this->project->build($backlog_item->getArtifact()->getTracker()->getProject());

        $this->parent = null;
        if ($backlog_item->getParent()) {
            $this->parent = new BacklogItemParentReference();
            $this->parent->build($backlog_item->getParent());
        }

        $this->has_children = $backlog_item->hasChildren();

        $this->addAllowedSubItemTypes($backlog_item);

        if ($card_fields) {
            $this->card_fields = $card_fields;
        }

        $this->background_color_name = $background_color->getBackgroundColorName();
    }

    private function addAllowedSubItemTypes(\AgileDashboard_Milestone_Backlog_IBacklogItem $backlog_item)
    {
        $child_trackers = $backlog_item->getArtifact()->getTracker()->getChildren();

        $this->accept = array('trackers' => array());
        foreach ($child_trackers as $child_tracker) {
            $reference = new TrackerReference();
            $reference->build($child_tracker);

            $this->accept['trackers'][] = $reference;
        }
    }
}
