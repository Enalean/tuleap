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

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\AddChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredTimeboxChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\NewChangesetCreationException;
use Tuleap\ProgramManagement\Domain\Workspace\ArtifactNotFoundException;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUser;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

final class ChangesetAdder implements AddChangeset
{
    public function __construct(
        private \Tracker_ArtifactFactory $artifact_factory,
        private RetrieveUser $user_retriever,
        private \Tracker_Artifact_Changeset_NewChangesetCreator $new_changeset_creator
    ) {
    }

    public function addChangeset(MirroredTimeboxChangeset $changeset): void
    {
        $artifact_id   = $changeset->mirrored_timebox->getId();
        $full_artifact = $this->artifact_factory->getArtifactById($artifact_id);
        if (! $full_artifact) {
            throw new ArtifactNotFoundException($artifact_id);
        }
        $full_user = $this->user_retriever->getUserWithId($changeset->user);

        try {
            $this->new_changeset_creator->create(
                $full_artifact,
                $changeset->values->toFieldsDataArray(),
                '',
                $full_user,
                $changeset->submission_date->getValue(),
                false,
                \Tracker_Artifact_Changeset_Comment::COMMONMARK_COMMENT,
                new CreatedFileURLMapping(),
                new TrackerNoXMLImportLoggedConfig(),
                []
            );
        } catch (\Tracker_NoChangeException $e) {
            // Ignore the exception if there is no new change in the changeset.
        } catch (FieldValidationException | \Tracker_Exception $exception) {
            throw new NewChangesetCreationException($artifact_id, $exception);
        }
    }
}
