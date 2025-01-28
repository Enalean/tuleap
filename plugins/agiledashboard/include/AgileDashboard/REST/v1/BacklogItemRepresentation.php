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

use Tuleap\AgileDashboard\Milestone\Backlog\IBacklogItem;
use Tuleap\Cardwall\BackgroundColor\BackgroundColor;
use Tuleap\Project\ProjectBackground\ProjectBackgroundConfiguration;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;
use Tuleap\Tracker\REST\TrackerReference;

/**
 * @psalm-immutable
 */
final readonly class BacklogItemRepresentation
{
    public const BACKLOG_ROUTE = 'backlog';

    public const CONTENT_ROUTE = 'content';

    public const ROUTE = 'backlog_items';

    private function __construct(
        public int $id,
        public string $label,
        public string $type,
        public string $short_type,
        public string $status,
        public string $color,
        public string $background_color_name,
        public ?float $initial_effort,
        public ?float $remaining_effort,
        public ArtifactReference $artifact,
        public ?BacklogItemParentReference $parent,
        public ProjectReference $project,
        public bool $has_children,
        public array $accept,
        public array $card_fields,
    ) {
    }

    public static function build(
        IBacklogItem $backlog_item,
        array $card_fields,
        BackgroundColor $background_color,
        ProjectBackgroundConfiguration $project_background_configuration,
    ): self {
        $item_parent = $backlog_item->getParent();

        $child_trackers = $backlog_item->getArtifact()->getTracker()->getChildren();

        $accept = ['trackers' => []];
        foreach ($child_trackers as $child_tracker) {
            $reference = TrackerReference::build($child_tracker);

            $accept['trackers'][] = $reference;
        }

        return new self(
            JsonCast::toInt($backlog_item->id()),
            $backlog_item->title(),
            $backlog_item->type(),
            $backlog_item->getShortType(),
            $backlog_item->getNormalizedStatusLabel(),
            $backlog_item->color(),
            $background_color->getBackgroundColorName(),
            JsonCast::toFloat($backlog_item->getInitialEffort()),
            JsonCast::toFloat($backlog_item->getRemainingEffort()),
            ArtifactReference::build($backlog_item->getArtifact()),
            $item_parent !== null ? BacklogItemParentReference::build($item_parent, $project_background_configuration) : null,
            new ProjectReference($backlog_item->getArtifact()->getTracker()->getProject()),
            $backlog_item->hasChildren(),
            $accept,
            $card_fields,
        );
    }
}
