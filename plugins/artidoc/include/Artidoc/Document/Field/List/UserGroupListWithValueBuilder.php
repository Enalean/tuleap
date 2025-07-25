<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Field\List;

use Tracker_FormElement_Field_List_BindValue;
use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserGroupListValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserGroupsListFieldWithValue;

final readonly class UserGroupListWithValueBuilder implements BuildUserGroupListWithValue
{
    #[\Override]
    public function buildUserGroupsListFieldWithValue(ConfiguredField $configured_field, ?\Tracker_Artifact_ChangesetValue_List $changeset_value): UserGroupsListFieldWithValue
    {
        return new UserGroupsListFieldWithValue(
            $configured_field->field->getLabel(),
            $configured_field->display_type,
            array_values(
                array_map(
                    static fn(Tracker_FormElement_Field_List_BindValue $value) => new UserGroupListValue(
                        $value->getLabel()
                    ),
                    $changeset_value?->getListValues() ?? [],
                )
            )
        );
    }
}
