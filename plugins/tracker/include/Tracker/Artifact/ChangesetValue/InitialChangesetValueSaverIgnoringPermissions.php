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

use PFUser;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\TrackerField;

/**
 * I save a new changeset for a field at a given date.
 *
 * This is used for import of history. This means that we cannot check
 * required fields or permissions as tracker structure has evolved between the
 * creation of the given artifact and now.
 */
final class InitialChangesetValueSaverIgnoringPermissions implements SaveInitialChangesetValue
{
    public function saveNewChangesetForField(
        TrackerField $field,
        Artifact $artifact,
        array $fields_data,
        PFUser $submitter,
        int $changeset_id,
        CreatedFileURLMapping $url_mapping,
        \Workflow $workflow,
    ): void {
        $is_submission = true;
        $bypass_perms  = true;
        if (isset($fields_data[$field->getId()])) {
            $field->saveNewChangeset(
                $artifact,
                null,
                $changeset_id,
                $fields_data[$field->getId()],
                $submitter,
                $is_submission,
                $bypass_perms,
                $url_mapping
            );
        }
    }
}
