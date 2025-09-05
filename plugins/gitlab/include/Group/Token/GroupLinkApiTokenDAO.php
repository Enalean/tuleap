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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Group\Token;

use Tuleap\DB\DataAccessObject;
use Tuleap\Gitlab\Group\GroupLink;

final class GroupLinkApiTokenDAO extends DataAccessObject implements GetTokenByGroupLinkId
{
    public function storeToken(int $group_id, string $encrypted_token): void
    {
        $this->getDB()->insertOnDuplicateKeyUpdate(
            'plugin_gitlab_group_token',
            [
                'group_id' => $group_id,
                'token'    => $encrypted_token,
            ],
            [
                'token',
            ]
        );
    }

    public function updateGitlabTokenOfGroupLink(GroupLink $group_link, string $gitlab_token): void
    {
        $this->getDB()->update(
            'plugin_gitlab_group_token',
            ['token' => $gitlab_token],
            ['group_id' => $group_link->id]
        );
    }

    #[\Override]
    public function getTokenByGroupId(int $group_id): string
    {
        return $this->getDB()->cell(
            'SELECT token FROM plugin_gitlab_group_token WHERE group_id = ?',
            $group_id
        );
    }
}
