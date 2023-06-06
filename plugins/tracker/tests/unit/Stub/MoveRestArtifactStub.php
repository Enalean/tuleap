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
use Tracker;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Exception\MoveArtifactNotDoneException;
use Tuleap\Tracker\Exception\MoveArtifactSemanticsException;
use Tuleap\Tracker\Exception\MoveArtifactTargetProjectNotActiveException;
use Tuleap\Tracker\REST\v1\Move\MoveRestArtifact;

/**
 * @psalm-immutable
 */
final class MoveRestArtifactStub implements MoveRestArtifact
{
    private int $call_count = 0;

    private function __construct(public int $remaining_deletions, public bool $move_artifact_not_done, public bool $move_semantic_exception, public bool $target_project_not_active)
    {
    }

    public static function andReturnRemainingDeletions(): self
    {
        return new self(9, false, false, false);
    }

    public static function andThrowMoveArtifactNotDone(): self
    {
        return new self(10, true, false, false);
    }

    public static function andThrowMoveArtifactSemanticsException(): self
    {
        return new self(10, false, true, false);
    }

    public static function andMoveArtifactTargetProjectNotActiveException(): self
    {
        return new self(10, false, false, true);
    }

    public function move(Tracker $source_tracker, Tracker $target_tracker, Artifact $artifact, PFUser $user, bool $should_populate_feedback_on_success): int
    {
        if ($this->move_artifact_not_done) {
            throw new MoveArtifactNotDoneException();
        }

        if ($this->move_semantic_exception) {
            throw new MoveArtifactSemanticsException("you can not");
        }

        if ($this->target_project_not_active) {
            throw new MoveArtifactTargetProjectNotActiveException();
        }
        $this->call_count++;
        return $this->remaining_deletions;
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }
}
