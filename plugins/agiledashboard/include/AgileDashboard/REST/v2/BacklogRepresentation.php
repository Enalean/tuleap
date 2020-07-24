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

use Tuleap\Tracker\REST\TrackerReference;

/**
 * @psalm-immutable
 */
class BacklogRepresentation
{
    public const ROUTE = 'backlog_items';

    /**
     * @var BacklogItemRepresentation[]
     */
    public $content;

    /**
     *
     * @var array
     */
    public $accept;

    /**
     * @var bool
     */
    public $has_user_priority_change_permission;

    /**
     * @param BacklogItemRepresentation[] $content
     */
    private function __construct(array $content, array $accept, bool $has_user_priority_change_permission)
    {
        $this->content                             = $content;
        $this->accept                              = $accept;
        $this->has_user_priority_change_permission = $has_user_priority_change_permission;
    }

    public static function build(
        array $backlog_items,
        array $accepted_trackers,
        array $parent_trackers,
        bool $has_user_priority_change_permission
    ): self {
        return new self(
            $backlog_items,
            [
                'trackers'        => self::getTrackersRepresentation($accepted_trackers),
                'parent_trackers' => self::getTrackersRepresentation($parent_trackers)
            ],
            $has_user_priority_change_permission
        );
    }

    /**
     * @return TrackerReference[]
     */
    private static function getTrackersRepresentation(array $trackers): array
    {
        $trackers_representation = [];
        foreach ($trackers as $tracker) {
            $tracker_reference = TrackerReference::build($tracker);
            $trackers_representation[] = $tracker_reference;
        }
        return $trackers_representation;
    }
}
