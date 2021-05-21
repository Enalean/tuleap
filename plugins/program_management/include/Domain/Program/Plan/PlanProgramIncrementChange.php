<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Plan;

/**
 * @psalm-immutable
 */
final class PlanProgramIncrementChange
{
    /**
     * @var int
     */
    public $tracker_id;
    /**
     * @var string|null
     */
    public $label;
    /**
     * @var string|null
     */
    public $sub_label;

    public function __construct(int $tracker_id, ?string $label, ?string $sub_label)
    {
        $this->tracker_id = $tracker_id;
        $this->label      = $label;
        $this->sub_label  = $sub_label;
    }
}
