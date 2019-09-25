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

namespace Tuleap\Tracker\Semantic\Status;

use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_List_BindValue;
use Tracker_Semantic_Status;

class StatusValueForChangesetProvider
{
    public function getStatusValueForChangeset(
        Tracker_Artifact_Changeset $changeset,
        PFUser $user
    ): ?Tracker_FormElement_Field_List_BindValue {
        $status_field = $this->loadSemantic($changeset)->getField();
        if (! $status_field) {
            return null;
        }

        if (! $status_field->userCanRead($user)) {
            return null;
        }

        $value = $changeset->getValue($status_field);
        if (! $value instanceof Tracker_Artifact_ChangesetValue_List) {
            return null;
        }

        $values = $value->getListValues();
        if (count($values) === 0) {
            return null;
        }

        reset($values);

        return current($values);
    }

    protected function loadSemantic(Tracker_Artifact_Changeset $changeset): Tracker_Semantic_Status
    {
        return Tracker_Semantic_Status::load($changeset->getArtifact()->getTracker());
    }
}
