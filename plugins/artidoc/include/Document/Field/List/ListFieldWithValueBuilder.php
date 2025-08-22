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

use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StaticListFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserGroupsListFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserListFieldWithValue;

final readonly class ListFieldWithValueBuilder
{
    public function __construct(
        private UserListFieldWithValueBuilder $user_list_field_with_value_builder,
        private StaticListFieldWithValueBuilder $static_list_field_with_value_builder,
        private UserGroupListWithValueBuilder $user_group_list_with_value_builder,
    ) {
    }

    public function buildListFieldWithValue(ConfiguredField $configured_field, ?\Tracker_Artifact_ChangesetValue_List $changeset_value): UserGroupsListFieldWithValue|StaticListFieldWithValue|UserListFieldWithValue
    {
        assert($configured_field->field instanceof \Tuleap\Tracker\FormElement\Field\ListField);

        return match ($configured_field->field->getBind()?->getType()) {
            \Tracker_FormElement_Field_List_Bind_Ugroups::TYPE => $this->user_group_list_with_value_builder->buildUserGroupsListFieldWithValue($configured_field, $changeset_value),
            \Tracker_FormElement_Field_List_Bind_Static::TYPE  => $this->static_list_field_with_value_builder->buildStaticListFieldWithValue($configured_field, $changeset_value),
            \Tracker_FormElement_Field_List_Bind_Users::TYPE   => $this->user_list_field_with_value_builder->buildUserListFieldWithValue($configured_field, $changeset_value),
            default => throw new \Exception("Unknown bind type for field #{$configured_field->field->getId()}"),
        };
    }
}
