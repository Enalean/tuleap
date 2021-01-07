<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

namespace Tuleap\Gitlab\Repository\Token;

use Tuleap\DB\DataAccessObject;

class GitlabBotApiTokenDao extends DataAccessObject
{

    public function storeToken(int $gitlab_repository_id, string $encrypted_token): void
    {
        $this->getDB()->insert(
            'plugin_gitlab_bot_api_token',
            [
                'gitlab_repository_id'    => $gitlab_repository_id,
                'token' => $encrypted_token
            ]
        );
    }

    public function deleteGitlabBotToken(int $gitlab_repository_id): void
    {
        $this->getDB()->delete(
            'plugin_gitlab_bot_api_token',
            ['gitlab_repository_id' => $gitlab_repository_id]
        );
    }
}
