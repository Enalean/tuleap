<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset;

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\XMLImport\TrackerImportConfig;
use Tuleap\Tracker\Changeset\Validation\ChangesetValidationContext;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;

interface CreateInitialChangeset
{
    /**
     * Create the initial changeset of an artifact
     *
     * @param array   $fields_data The artifact fields values
     * @param \PFUser  $submitter   The user who did the artifact submission
     * @param int $submitted_on When the changeset is created
     *
     * @return int|null The Id of the initial changeset, or null if fields were not valid
     */
    public function create(
        Artifact $artifact,
        array $fields_data,
        \PFUser $submitter,
        int $submitted_on,
        CreatedFileURLMapping $url_mapping,
        TrackerImportConfig $import_config,
        ChangesetValidationContext $changeset_validation_context,
    ): ?int;
}
