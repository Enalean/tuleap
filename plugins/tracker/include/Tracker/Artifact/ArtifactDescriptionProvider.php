<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_Semantic_Description;

class ArtifactDescriptionProvider
{
    /**
     * @var Tracker_Semantic_Description
     */
    private $semantic_description;

    public function __construct(Tracker_Semantic_Description $semantic_description)
    {
        $this->semantic_description = $semantic_description;
    }

    public function getDescription(Tracker_Artifact $artifact): string
    {
        $description_field = $this->semantic_description->getField();
        if (! $description_field) {
            return '';
        }

        if (! $description_field->userCanRead()) {
            return '';
        }

        $last_changeset = $artifact->getLastChangeset();
        if (! $last_changeset) {
            return '';
        }

        $description_field_value = $last_changeset->getValue($description_field);
        if (! $description_field_value instanceof Tracker_Artifact_ChangesetValue_Text) {
            return '';
        }

        return $description_field_value->getContentAsText();
    }
}
