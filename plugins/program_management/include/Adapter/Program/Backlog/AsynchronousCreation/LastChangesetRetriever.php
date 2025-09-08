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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\RetrieveFullArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\RetrieveLastChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;

final class LastChangesetRetriever implements RetrieveLastChangeset
{
    public function __construct(
        private RetrieveFullArtifact $artifact_retriever,
        private \Tracker_Artifact_ChangesetFactory $changeset_factory,
    ) {
    }

    #[\Override]
    public function retrieveLastChangesetId(TimeboxIdentifier $timebox_identifier): ?int
    {
        $iteration_artifact = $this->artifact_retriever->getNonNullArtifact($timebox_identifier);
        $changeset          = $this->changeset_factory->getLastChangeset($iteration_artifact);
        if ($changeset === null) {
            return null;
        }
        return (int) $changeset->getId();
    }
}
