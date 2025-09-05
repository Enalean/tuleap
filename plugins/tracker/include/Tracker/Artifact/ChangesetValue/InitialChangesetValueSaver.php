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
 * I save the initial changeset of an artifact for a given field
 */
final class InitialChangesetValueSaver implements SaveInitialChangesetValue
{
    #[\Override]
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

        if ($this->isFieldSubmitted($field, $fields_data)) {
            if ($field->userCanSubmit($submitter)) {
                $field->saveNewChangeset(
                    $artifact,
                    null,
                    $changeset_id,
                    $fields_data[$field->getId()],
                    $submitter,
                    $is_submission,
                    false,
                    $url_mapping
                );

                return;
            }

            if ($workflow->bypassPermissions($field)) {
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

                return;
            }
        }

        if (! $field->userCanSubmit() && $field->isSubmitable()) {
            $this->pushDefaultValueInSubmittedValues($field, $fields_data);
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

    private function pushDefaultValueInSubmittedValues(TrackerField $field, array &$fields_data): void
    {
        $fields_data[$field->getId()] = $field->getDefaultValue();
    }

    private function isFieldSubmitted(TrackerField $field, array $fields_data): bool
    {
        return isset($fields_data[$field->getId()]);
    }
}
