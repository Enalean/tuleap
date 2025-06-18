<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Progress;

use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
final class SemanticProgressBasedOnEffortRepresentation implements IRepresentSemanticProgress
{
    /**
     * @var int
     */
    public $total_effort_field_id;
    /**
     * @var int
     */
    public $remaining_effort_field_id;

    public function __construct(int $total_effort_field_id, int $remaining_effort_field_id)
    {
        $this->total_effort_field_id     = JsonCast::toInt($total_effort_field_id);
        $this->remaining_effort_field_id = JsonCast::toInt($remaining_effort_field_id);
    }
}
