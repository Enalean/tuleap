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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset;

use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\RetrieveFullArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ChangesetIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\ChangesetNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\RetrieveChangesetSubmissionDate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\SubmissionDate;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Artifact\ArtifactIdentifier;

final class SubmissionDateRetriever implements RetrieveChangesetSubmissionDate
{
    public function __construct(
        private RetrieveFullArtifact $artifact_retriever,
    ) {
    }

    #[\Override]
    public function getSubmissionDate(
        ArtifactIdentifier $artifact,
        ChangesetIdentifier $changeset_identifier,
    ): SubmissionDate {
        $full_artifact = $this->artifact_retriever->getNonNullArtifact($artifact);
        $changeset     = $full_artifact->getChangeset($changeset_identifier->getId());
        if (! $changeset) {
            throw new ChangesetNotFoundException($changeset_identifier->getId());
        }
        return SubmissionDateProxy::fromChangeset($changeset);
    }
}
