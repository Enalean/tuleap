<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Team\MirroredTimebox;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I hold the identifier of an Artifact of the Mirrored Iteration Tracker of a Team.
 * See "glossary.md" in the "adr" folder which explains what is a Mirrored Iteration.
 * @psalm-immutable
 */
final class MirroredIterationIdentifier implements MirroredTimeboxIdentifier
{
    private function __construct(private int $id)
    {
    }

    /**
     * @return self[]
     */
    public static function buildCollectionFromIteration(
        SearchMirroredTimeboxes $iteration_searcher,
        VerifyIsVisibleArtifact $artifact_visibility_verifier,
        IterationIdentifier $iteration,
        UserIdentifier $user,
    ): array {
        $mirrored_iterations_ids      = $iteration_searcher->searchMirroredTimeboxes($iteration);
        $valid_mirrored_iterations_id = [];
        foreach ($mirrored_iterations_ids as $id) {
            if ($artifact_visibility_verifier->isVisible($id, $user)) {
                $valid_mirrored_iterations_id[] = new self($id);
            }
        }
        return $valid_mirrored_iterations_id;
    }

    #[\Override]
    public function getId(): int
    {
        return $this->id;
    }
}
