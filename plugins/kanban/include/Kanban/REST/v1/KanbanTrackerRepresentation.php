<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Kanban\REST\v1;

use AgileDashboard_Kanban;
use TrackerFactory;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\Tracker\REST\CompleteTrackerRepresentation;

/**
 * @psalm-immutable
 */
final class KanbanTrackerRepresentation
{
    /**
     * @var string URI of the tracker
     */
    public $uri;

    private function __construct(
        public readonly int $id,
        public readonly string $label,
        public readonly string $item_name,
        public readonly ProjectReference $project,
    ) {
        $this->uri = CompleteTrackerRepresentation::ROUTE . '/' . $id;
    }

    public static function fromKanban(TrackerFactory $tracker_factory, AgileDashboard_Kanban $kanban): self
    {
        $tracker = self::getTracker($tracker_factory, $kanban);
        return new self(
            $tracker->getId(),
            $tracker->getName(),
            $tracker->getItemName(),
            new ProjectReference($tracker->getProject())
        );
    }

    private static function getTracker(TrackerFactory $tracker_factory, AgileDashboard_Kanban $kanban): \Tracker
    {
        $tracker_id = $kanban->getTrackerId();
        $tracker    = $tracker_factory->getTrackerById($tracker_id);

        if ($tracker === null) {
            throw new \RuntimeException(
                sprintf("Cannot find the tracker #%d associated with the kanban #%d", $tracker_id, $kanban->getId())
            );
        }

        return $tracker;
    }
}
