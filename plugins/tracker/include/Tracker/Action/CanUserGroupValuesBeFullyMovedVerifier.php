<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\User\UserGroup\NameTranslator;

final class CanUserGroupValuesBeFullyMovedVerifier implements VerifyUserGroupValuesCanBeFullyMoved
{
    public function __construct()
    {
    }

    public function canAllUserGroupFieldValuesBeMoved(\Tracker_FormElement_Field_List $source_field, \Tracker_FormElement_Field_List $target_field, Artifact $artifact): bool
    {
        $last_changeset_value = $source_field->getLastChangesetValue($artifact);
        if (! $last_changeset_value instanceof \Tracker_Artifact_ChangesetValue_List) {
            return false;
        }

        $list_field_values          = array_values($last_changeset_value->getListValues());
        $destinations_values_labels = array_map(static fn($value) => $value->getLabel(), $target_field->getAllValues());

        foreach ($list_field_values as $value) {
            assert($value instanceof \Tracker_FormElement_Field_List_Bind_UgroupsValue);
            $user_group_name = NameTranslator::getUserGroupDisplayName($value->getUGroupName());

            if (! in_array($user_group_name, $destinations_values_labels, true)) {
                return false;
            }
        }

        return true;
    }
}
