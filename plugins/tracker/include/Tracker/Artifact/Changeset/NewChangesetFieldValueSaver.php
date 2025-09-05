<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\ChangesetValue\SaveChangesetValue;

final readonly class NewChangesetFieldValueSaver implements StoreNewChangesetFieldValues
{
    public function __construct(
        private FieldsToBeSavedInSpecificOrderRetriever $fields_retriever,
        private SaveChangesetValue $changeset_value_saver,
    ) {
    }

    /**
     * @throws \Tracker_FieldValueNotStoredException
     */
    #[\Override]
    public function storeFieldsValues(
        NewChangeset $new_changeset,
        ?\Tracker_Artifact_Changeset $previous_changeset,
        array $fields_data,
        int $changeset_id,
        \Workflow $workflow,
    ): void {
        $artifact = $new_changeset->getArtifact();
        foreach ($this->fields_retriever->getFields($artifact) as $field) {
            if (
                ! $this->changeset_value_saver->saveNewChangesetForField(
                    $field,
                    $artifact,
                    $previous_changeset,
                    $fields_data,
                    $new_changeset->getSubmitter(),
                    $changeset_id,
                    $workflow,
                    $new_changeset->getUrlMapping()
                )
            ) {
                $purifier = \Codendi_HTMLPurifier::instance();
                throw new \Tracker_FieldValueNotStoredException(
                    sprintf(
                        dgettext('tuleap-tracker', 'The field "%1$s" cannot be stored.'),
                        $purifier->purify($field->getLabel())
                    )
                );
            }
        }
    }
}
