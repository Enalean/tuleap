<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\User;

/**
 * @psalm-immutable
 */
final class JiraUserBuilder
{
    public static function getUserFromPayload(array $user_payload): JiraUser
    {
        if (isset($user_payload['displayName'], $user_payload['accountId'])) {
            return new ActiveJiraCloudUser($user_payload);
        }

        if (
            isset($user_payload['displayName'], $user_payload['name'])
            && is_string($user_payload['displayName'])
            && is_string($user_payload['name'])
            && (
                ! isset($user_payload['emailAddress'])
                || (
                    isset($user_payload['emailAddress'])
                    && is_string($user_payload['emailAddress'])
                )
            )
        ) {
            return ActiveJiraServerUser::buildFromPayload($user_payload);
        }

        throw new JiraMinimalUserInformationMissingException();
    }
}
