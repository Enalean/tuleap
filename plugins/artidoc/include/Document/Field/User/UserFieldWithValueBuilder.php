<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Field\User;

use Exception;
use Tracker_Artifact_Changeset;
use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserValue;
use Tuleap\Tracker\FormElement\Field\LastUpdateBy\LastUpdateByField;
use Tuleap\Tracker\FormElement\Field\SubmittedBy\SubmittedByField;
use Tuleap\User\Avatar\ProvideDefaultUserAvatarUrl;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\BuildDisplayName;
use Tuleap\User\ProvideAnonymousUser;
use Tuleap\User\RetrieveUserById;

final readonly class UserFieldWithValueBuilder
{
    public function __construct(
        private RetrieveUserById $retrieve_user_by_id,
        private ProvideAnonymousUser $provide_anonymous_user,
        private ProvideUserAvatarUrl $provide_user_avatar_url,
        private ProvideDefaultUserAvatarUrl $provide_default_user_avatar_url,
        private BuildDisplayName $build_display_name,
    ) {
    }

    public function buildUserFieldWithValue(ConfiguredField $configured_field, Tracker_Artifact_Changeset $changeset): UserFieldWithValue
    {
        return new UserFieldWithValue(
            $configured_field->field->getLabel(),
            $configured_field->display_type,
            $this->buildUserValue($configured_field, $changeset),
        );
    }

    private function buildUserValue(ConfiguredField $configured_field, Tracker_Artifact_Changeset $changeset): UserValue
    {
        if ($configured_field->field instanceof LastUpdateByField) {
            $user_id = (int) $changeset->getSubmittedBy();
            if ($user_id === \PFUser::ANONYMOUS_USER_ID) {
                $user_mail = $changeset->getEmail() ?? '';
                return new UserValue(
                    $user_mail,
                    $this->provide_default_user_avatar_url->getDefaultAvatarUrl(),
                );
            }
        } elseif ($configured_field->field instanceof SubmittedByField) {
            $user_id = $changeset->getArtifact()->getSubmittedBy();
        } else {
            throw new Exception('Unknown field type: ' . $configured_field->field::class);
        }

        $user = $this->retrieve_user_by_id->getUserById($user_id) ?? $this->provide_anonymous_user->getUserAnonymous();
        return new UserValue(
            $this->build_display_name->getDisplayName($user->getUserName(), $user->getRealName()),
            $this->provide_user_avatar_url->getAvatarUrl($user),
        );
    }
}
