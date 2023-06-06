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
use Tuleap\Tracker\Action\CheckMoveArtifact;
use Tuleap\Tracker\Action\Move\FeedbackFieldCollectorInterface;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Exception\MoveArtifactSemanticsException;

/**
 * @psalm-immutable
 */
final class CheckMoveArtifactStub implements CheckMoveArtifact
{
    private function __construct(private readonly bool $is_move_possible)
    {
    }

    public static function withPossibleArtifactMove(): self
    {
        return new self(true);
    }

    public static function withoutPossibleArtifactMove(): self
    {
        return new self(false);
    }

    /**
     * @throws MoveArtifactSemanticsException
     */
    public function checkMoveIsPossible(
        Artifact $artifact,
        Tracker $target_tracker,
        PFUser $user,
        FeedbackFieldCollectorInterface $feedback_field_collector,
    ): void {
        if ($this->is_move_possible) {
            return;
        }

        throw new MoveArtifactSemanticsException("Artifact move is not possible");
    }
}
