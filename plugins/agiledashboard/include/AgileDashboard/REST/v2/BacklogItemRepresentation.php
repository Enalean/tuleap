<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v2;

use Tuleap\AgileDashboard\REST\v1\BacklogItemParentReference;
use Tuleap\REST\JsonCast;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;
use Tuleap\Tracker\REST\TrackerReference;

/**
 * @psalm-immutable
 */
class BacklogItemRepresentation
{

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
     * @var float | null
     */
    public $initial_effort;

    /**
     * @var \Tuleap\Tracker\REST\Artifact\ArtifactReference
     */
    public $artifact;

    /**
     * @var \Tuleap\AgileDashboard\REST\v1\BacklogItemParentReference | null
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
    public $card_fields = [];

    public function __construct(
        int $id,
        string $label,
        string $status,
        string $type,
        string $short_type,
        ?float $initial_effort,
        string $color,
        ArtifactReference $artifact,
        ProjectReference $project,
        ?BacklogItemParentReference $parent,
        bool $has_children,
        array $accept,
        array $card_fields
    ) {
        $this->id             = $id;
        $this->label          = $label;
        $this->status         = $status;
        $this->type           = $type;
        $this->short_type     = $short_type;
        $this->initial_effort = $initial_effort;
        $this->color          = $color;
        $this->artifact       = $artifact;
        $this->project        = $project;
        $this->parent         = $parent;
        $this->has_children   = $has_children;
        $this->accept         = $accept;
        $this->card_fields    = $card_fields;
    }

    public static function build(\AgileDashboard_Milestone_Backlog_IBacklogItem $backlog_item, array $card_fields): self
    {
        $parent = null;
        if ($backlog_item->getParent()) {
            $parent = BacklogItemParentReference::build($backlog_item->getParent());
        }

        return new self(
            JsonCast::toInt($backlog_item->id()),
            $backlog_item->title(),
            $backlog_item->getStatus(),
            $backlog_item->type(),
            $backlog_item->getShortType(),
            JsonCast::toFloat($backlog_item->getInitialEffort()),
            $backlog_item->color(),
            ArtifactReference::build($backlog_item->getArtifact()),
            new ProjectReference($backlog_item->getArtifact()->getTracker()->getProject()),
            $parent,
            $backlog_item->hasChildren(),
            self::addAllowedSubItemTypes($backlog_item),
            $card_fields
        );
    }

    private static function addAllowedSubItemTypes(\AgileDashboard_Milestone_Backlog_IBacklogItem $backlog_item): array
    {
        $child_trackers = $backlog_item->getArtifact()->getTracker()->getChildren();

        $accept = ['trackers' => []];
        foreach ($child_trackers as $child_tracker) {
            $reference = TrackerReference::build($child_tracker);

            $accept['trackers'][] = $reference;
        }

        return $accept;
    }
}
