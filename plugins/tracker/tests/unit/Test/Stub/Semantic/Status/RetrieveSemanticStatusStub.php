<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub\Semantic\Status;

use Tuleap\Tracker\Semantic\Status\RetrieveSemanticStatus;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;
use Tuleap\Tracker\Tracker;

final class RetrieveSemanticStatusStub implements RetrieveSemanticStatus
{
    private int $call_count = 0;
    /** @var array<int, TrackerSemanticStatus> */
    private array $semantics = [];

    private function __construct()
    {
    }

    public static function build(): self
    {
        return new self();
    }

    public function withSemanticStatus(TrackerSemanticStatus $semantic_status): self
    {
        $this->semantics[$semantic_status->getTracker()->getId()] = $semantic_status;
        return $this;
    }

    #[\Override]
    public function fromTracker(Tracker $tracker): TrackerSemanticStatus
    {
        $this->call_count++;
        if (! isset($this->semantics[$tracker->getId()])) {
            throw new \Exception('No semantic defined for tracker #' . $tracker->getId());
        }

        return $this->semantics[$tracker->getId()];
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }
}
