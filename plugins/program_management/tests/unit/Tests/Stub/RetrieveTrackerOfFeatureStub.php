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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveTrackerOfFeature;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;

final class RetrieveTrackerOfFeatureStub implements RetrieveTrackerOfFeature
{
    private function __construct(private array $ids)
    {
    }

    public static function withId(int $tracker_id): self
    {
        return new self([$tracker_id]);
    }

    /**
     * @no-named-arguments
     */
    public static function withSuccessiveIds(int $tracker_id, int ...$other_ids): self
    {
        return new self([$tracker_id, ...$other_ids]);
    }

    #[\Override]
    public function getFeatureTracker(FeatureIdentifier $feature): TrackerIdentifier
    {
        if (count($this->ids) > 0) {
            return TrackerIdentifierStub::withId(array_shift($this->ids));
        }
        throw new \LogicException('No tracker id configured');
    }
}
