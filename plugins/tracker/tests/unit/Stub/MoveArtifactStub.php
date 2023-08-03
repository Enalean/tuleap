<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub;

use PFUser;
use Psr\Log\LoggerInterface;
use Tracker;
use Tuleap\Tracker\Action\Move\FeedbackFieldCollectorInterface;
use Tuleap\Tracker\Action\MoveArtifact;
use Tuleap\Tracker\Artifact\Artifact;

/**
 * @psalm-immutable
 */
final class MoveArtifactStub implements MoveArtifact
{
    private int $call_count = 0;

    private function __construct(public int $remaining_deletions)
    {
    }

    public static function andReturnRemainingDeletions(): self
    {
        return new self(random_int(1, 1000));
    }

    public function move(Artifact $artifact, Tracker $destination_tracker, PFUser $user, FeedbackFieldCollectorInterface $feedback_field_collector, LoggerInterface $logger): int
    {
        $this->call_count++;
        return $this->remaining_deletions;
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }
}
