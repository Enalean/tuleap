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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature;

/**
 * @psalm-immutable
 */
final class ProgramIncrementChanged
{
    /**
     * @var int
     */
    public $program_increment_id;
    /**
     * @var int
     */
    public $tracker_id;
    /**
     * @var \PFUser
     */
    public $user;

    public function __construct(int $program_increment_id, int $tracker_id, \PFUser $user)
    {
        $this->program_increment_id = $program_increment_id;
        $this->tracker_id           = $tracker_id;
        $this->user                 = $user;
    }
}
