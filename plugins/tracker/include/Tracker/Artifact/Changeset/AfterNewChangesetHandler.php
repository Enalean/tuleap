<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset;

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\SaveArtifact;
use Tuleap\Tracker\Workflow\RetrieveWorkflow;

final class AfterNewChangesetHandler
{
    public function __construct(
        private SaveArtifact $artifact_saver,
        private FieldsToBeSavedInSpecificOrderRetriever $fields_retriever,
        private RetrieveWorkflow $workflow_retriever,
    ) {
    }

    public function handle(
        Artifact $artifact,
        array $fields_data,
        \PFUser $submitter,
        \Tracker_Artifact_Changeset $new_changeset,
        ?\Tracker_Artifact_Changeset $previous_changeset = null,
    ): bool {
        if (! $this->artifact_saver->save($artifact)) {
            return false;
        }
        foreach ($this->fields_retriever->getFields($artifact) as $field) {
            $field->postSaveNewChangeset($artifact, $submitter, $new_changeset, $fields_data, $previous_changeset);
        }
        $workflow = $this->workflow_retriever->getNonNullWorkflow($artifact->getTracker());
        $workflow->after($fields_data, $new_changeset, $previous_changeset);

        ChangesetInstrumentation::increment();
        return true;
    }
}
