<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
final class ProgramPlannableTracker
{
    /**
     * @var int
     */
    private $id;

    private function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * @throws \Tuleap\ProgramManagement\Domain\Program\ProgramTrackerException
     */
    public static function build(
        BuildTracker $build_tracker,
        int $tracker_id,
        int $project_id
    ): self {
        $build_tracker->checkTrackerIsValid($tracker_id, $project_id);

        return new self($tracker_id);
    }

    public function getId(): int
    {
        return $this->id;
    }
}
