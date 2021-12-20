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

namespace Tuleap\ProgramManagement\Domain\Team\MirroredTimebox;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifierCollection;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * @psalm-immutable
 */
final class MirroredIterationIdentifierCollection
{
    /**
     * @param MirroredIterationIdentifier[] $mirrored_iteration_identifiers
     */
    private function __construct(private array $mirrored_iteration_identifiers)
    {
    }

    public static function fromIteration(
        SearchMirroredTimeboxes $iteration_searcher,
        VerifyIsVisibleArtifact $visibility_verifier,
        IterationIdentifier $iteration,
        UserIdentifier $user,
    ): self {
        $mirrors = MirroredIterationIdentifier::buildCollectionFromIteration(
            $iteration_searcher,
            $visibility_verifier,
            $iteration,
            $user
        );
        return new self($mirrors);
    }

    public static function fromIterationCollection(
        SearchMirroredTimeboxes $iteration_searcher,
        VerifyIsVisibleArtifact $visibility_verifier,
        IterationIdentifierCollection $iterations,
        UserIdentifier $user,
    ): self {
        $all_mirrors = [];
        foreach ($iterations->getIterations() as $iteration) {
            $mirrors_of_iteration = MirroredIterationIdentifier::buildCollectionFromIteration(
                $iteration_searcher,
                $visibility_verifier,
                $iteration,
                $user
            );
            array_push($all_mirrors, ...$mirrors_of_iteration);
        }
        return new self($all_mirrors);
    }

    /**
     * @return MirroredIterationIdentifier[]
     */
    public function getMirroredIterations(): array
    {
        return $this->mirrored_iteration_identifiers;
    }
}
