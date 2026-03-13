<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\REST\v1\TrackerFieldRepresentations;

use Tuleap\Tracker\REST\v1\TrackerFieldRepresentations\MoveTrackerFieldsPATCHRepresentation;

/**
 * @psalm-immutable
 */
class TrackerFieldPatchRepresentation
{
    /**
     * @var string | null The new label of the form element {@required false}
     */
    public ?string $label = null;

    /**
     * @var array | null The new values for list field {@type string} {@required false}
     */
    public $new_values;

    /**
     * @var bool | null Unuse or use the form element{@type bool} {@required false}
     */
    public ?bool $use_it = null;

    /**
     * @var MoveTrackerFieldsPATCHRepresentation | null {@type Tuleap\Tracker\REST\v1\TrackerFieldRepresentations\MoveTrackerFieldsPATCHRepresentation} {@required false}
     */
    public ?MoveTrackerFieldsPATCHRepresentation $move = null;

    public function __construct(?string $label, array $new_values, ?bool $use_it, ?MoveTrackerFieldsPATCHRepresentation $move)
    {
        $this->label      = $label;
        $this->new_values = $new_values;
        $this->use_it     = $use_it;
        $this->move       = $move;
    }
}
