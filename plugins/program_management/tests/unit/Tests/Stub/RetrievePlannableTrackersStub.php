<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

use Tuleap\ProgramManagement\Domain\Program\Plan\RetrievePlannableTrackers;
use Tuleap\ProgramManagement\Domain\TrackerReference;

/**
 * @psalm-immutable
 */
final class RetrievePlannableTrackersStub implements RetrievePlannableTrackers
{
    /**
     * @var TrackerReference[]
     */
    private array $tracker_references;

    /**
     * @param TrackerReference[] $tracker_references
     */
    public function __construct(array $tracker_references)
    {
        $this->tracker_references = $tracker_references;
    }

    /**
     * @return TrackerReference[]
     */
    #[\Override]
    public function getPlannableTrackersOfProgram(int $program_id): array
    {
        return $this->tracker_references;
    }

    public static function build(TrackerReference ...$tracker_references): self
    {
        return new self($tracker_references);
    }
}
