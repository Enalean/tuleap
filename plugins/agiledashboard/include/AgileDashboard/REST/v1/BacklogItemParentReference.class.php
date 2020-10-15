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

use Tuleap\REST\JsonCast;
use Tuleap\REST\ResourceReference;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\TrackerReference;

/**
 * @psalm-immutable
 */
class BacklogItemParentReference
{
    /**
     * @var int ID of the backlog item
     */
    public $id;

    /**
     * @var String
     */
    public $label;

    /**
     * @var string URI of backlog item
     */
    public $uri = ResourceReference::NO_ROUTE;

    /**
     * @var \Tuleap\Tracker\REST\TrackerReference
     */
    public $tracker;

    private function __construct(int $id, string $label, TrackerReference $tracker)
    {
        $this->id      = $id;
        $this->label   = $label;
        $this->tracker = $tracker;
    }

    public static function build(Artifact $backlog_item): self
    {
        return new self(
            JsonCast::toInt($backlog_item->getId()),
            $backlog_item->getTitle() ?? '',
            TrackerReference::build($backlog_item->getTracker())
        );
    }
}
