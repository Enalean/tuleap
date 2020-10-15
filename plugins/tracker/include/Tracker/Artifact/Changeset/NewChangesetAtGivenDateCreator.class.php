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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

/**
 * I create a new changeset (update of an artifact) at a given date.
 *
 * This is used for import of history. This means that we cannot check
 * required fields or permissions as tracker structure has evolved between the
 * creation of the given artifact and now.
 */
class Tracker_Artifact_Changeset_NewChangesetAtGivenDateCreator extends Tracker_Artifact_Changeset_NewChangesetCreatorBase
{
    protected function saveNewChangesetForField(
        Tracker_FormElement_Field $field,
        Artifact $artifact,
        $previous_changeset,
        array $fields_data,
        PFUser $submitter,
        $changeset_id,
        CreatedFileURLMapping $url_mapping
    ): bool {
        $is_submission = false;
        $bypass_perms  = true;

        if ($this->isFieldSubmitted($field, $fields_data)) {
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
            $bypass_perms,
            $url_mapping
        );
    }
}
