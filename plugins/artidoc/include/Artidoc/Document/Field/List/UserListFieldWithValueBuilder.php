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

use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_BindValue;
use Tracker_FormElement_Field_List_OpenValue;
use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserListFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserListValue;
use Tuleap\User\Avatar\ProvideDefaultUserAvatarUrl;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\RetrieveUserById;

final readonly class UserListFieldWithValueBuilder implements BuildUserListFieldWithValue
{
    public function __construct(
        private RetrieveUserById $retrieve_user_by_id,
        private ProvideUserAvatarUrl $provide_user_avatar_url,
        private ProvideDefaultUserAvatarUrl $provide_default_user_avatar_url,
    ) {
    }

    #[\Override]
    public function buildUserListFieldWithValue(ConfiguredField $configured_field, ?\Tracker_Artifact_ChangesetValue_List $changeset_value): UserListFieldWithValue
    {
        return new UserListFieldWithValue(
            $configured_field->field->getLabel(),
            $configured_field->display_type,
            array_values(
                array_map(
                    function (Tracker_FormElement_Field_List_BindValue|Tracker_FormElement_Field_List_OpenValue $value) {
                        return new UserListValue(
                            $value->getLabel(),
                            $this->getAvatarUrl($value),
                        );
                    },
                    array_filter(
                        $changeset_value?->getListValues() ?? [],
                        fn ($value) => $value->getId() !== Tracker_FormElement_Field_List::NONE_VALUE,
                    ),
                )
            )
        );
    }

    private function getAvatarUrl(Tracker_FormElement_Field_List_BindValue|Tracker_FormElement_Field_List_OpenValue $value): string
    {
        if ($value instanceof Tracker_FormElement_Field_List_OpenValue) {
            return $this->provide_default_user_avatar_url->getDefaultAvatarUrl();
        }

        $user = $this->retrieve_user_by_id->getUserById($value->getId());

        return $user !== null ? $this->provide_user_avatar_url->getAvatarUrl($user) : $this->provide_default_user_avatar_url->getDefaultAvatarUrl();
    }
}
