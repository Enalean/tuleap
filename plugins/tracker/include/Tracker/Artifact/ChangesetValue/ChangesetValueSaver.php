<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ChangesetValue;

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;

final class ChangesetValueSaver implements SaveChangesetValue
{
    #[\Override]
    public function saveNewChangesetForField(
        \Tuleap\Tracker\FormElement\Field\TrackerField $field,
        Artifact $artifact,
        ?\Tracker_Artifact_Changeset $previous_changeset,
        array $fields_data,
        \PFUser $submitter,
        int $changeset_id,
        \Workflow $workflow,
        CreatedFileURLMapping $url_mapping,
    ): bool {
        $is_submission = false;
        $bypass_perms  = true;

        if ($this->isFieldSubmitted($field, $fields_data)) {
            if ($field->userCanUpdate($submitter)) {
                return $field->saveNewChangeset(
                    $artifact,
                    $previous_changeset,
                    $changeset_id,
                    $fields_data[$field->getId()],
                    $submitter,
                    $is_submission,
                    false,
                    $url_mapping
                );
            }

            if ($workflow->bypassPermissions($field)) {
                return $field->saveNewChangeset(
                    $artifact,
                    $previous_changeset,
                    $changeset_id,
                    $fields_data[$field->getId()],
                    $submitter,
                    $is_submission,
                    $bypass_perms,
                    $url_mapping
                );
            }

            return $field->saveNewChangeset(
                $artifact,
                $previous_changeset,
                $changeset_id,
                null,
                $submitter,
                $is_submission,
                false,
                $url_mapping
            );
        }

        return $field->saveNewChangeset(
            $artifact,
            $previous_changeset,
            $changeset_id,
            null,
            $submitter,
            $is_submission,
            false,
            $url_mapping
        );
    }

    private function isFieldSubmitted(\Tuleap\Tracker\FormElement\Field\TrackerField $field, array $fields_data): bool
    {
        return isset($fields_data[$field->getId()]);
    }
}
