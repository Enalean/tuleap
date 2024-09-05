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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveVisibleIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class RetrieveVisibleIterationTrackerStub implements RetrieveVisibleIterationTracker
{
    private ?TrackerReference $tracker;
    private bool $is_visible;

    private function __construct(?TrackerReference $tracker, bool $is_visible)
    {
        $this->tracker    = $tracker;
        $this->is_visible = $is_visible;
    }

    public function retrieveVisibleIterationTracker(ProgramIdentifier $program, UserIdentifier $user_identifier): ?TrackerReference
    {
        if (! $this->is_visible) {
            return null;
        }
        if ($this->tracker === null) {
            throw new ProgramTrackerNotFoundException($program->getId());
        }
        return $this->tracker;
    }

    public static function withValidTracker(TrackerReference $tracker): self
    {
        return new self($tracker, true);
    }

    public static function withNotVisibleIterationTracker(): self
    {
        return new self(null, false);
    }
}
