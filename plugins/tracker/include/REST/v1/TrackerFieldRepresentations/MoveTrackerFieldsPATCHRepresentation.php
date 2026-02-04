<?php
/**
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\TrackerFieldRepresentations;

/**
 * @psalm-immutable
 */
final class MoveTrackerFieldsPATCHRepresentation
{
    /**
     * @var int | null The id of the parent element. Provide null to move field at tracker root {@required false}
     */
    public ?int $parent_id;

    /**
     * @var int | null The id of the next sibling in parent. Provide null to move field at the end of the parent field {@required false}
     */
    public ?int $next_sibling_id;

    public function __construct(?int $parent_id = null, ?int $next_sibling_id = null)
    {
        $this->parent_id       = $parent_id;
        $this->next_sibling_id = $next_sibling_id;
    }
}
