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

namespace integration\Group;

use DateTimeImmutable;
use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DBFactory;
use Tuleap\Gitlab\Group\GroupLink;
use Tuleap\Gitlab\Group\Token\GroupLinkApiTokenDAO;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class GitlabApiTokenDAOTest extends TestIntegrationTestCase
{
    private GroupLinkApiTokenDAO $token_dao;

    private const GROUP_LINK_ID           = 1;
    private const ENCRYPTED_TOKEN         = 'Oxt0ken1';
    private const UPDATED_ENCRYPTED_TOKEN = 'Oxt0ken1_update';

    protected function setUp(): void
    {
        $this->token_dao = new GroupLinkApiTokenDAO();
    }

    private function getDB(): EasyDB
    {
        return DBFactory::getMainTuleapDBConnection()->getDB();
    }

    public function testCRUD(): void
    {
        $this->addTokenToGroupLink();
        $this->updateTokenOfGroupLink();
    }

    private function addTokenToGroupLink(): void
    {
        $this->token_dao->storeToken(self::GROUP_LINK_ID, self::ENCRYPTED_TOKEN);

        $row = $this->getGroupToken();
        self::assertSame(self::GROUP_LINK_ID, $row["group_id"]);
        self::assertSame(self::ENCRYPTED_TOKEN, $row["token"]);
    }

    private function updateTokenOfGroupLink(): void
    {
        $this->token_dao->updateGitlabTokenOfGroupLink($this->buildGroupLink(), self::UPDATED_ENCRYPTED_TOKEN);
        $row = $this->getGroupToken();
        self::assertSame(self::GROUP_LINK_ID, $row["group_id"]);
        self::assertSame(self::UPDATED_ENCRYPTED_TOKEN, $row["token"]);
    }

    private function getGroupToken(): mixed
    {
        $sql = 'SELECT * FROM plugin_gitlab_group_token';
        return $this->getDB()->row($sql);
    }

    private function buildGroupLink(): GroupLink
    {
        $row = [
            'id'                        => self::GROUP_LINK_ID,
            'gitlab_group_id'           => 10,
            'project_id'                => 102,
            'name'                      => 'Troublemaker',
            'full_path'                 => 'https://gitlab.example.com/Troublemaker/',
            'web_url'                   => 'https://gitlab.example.com/Troublemaker/',
            'avatar_url'                => 'https://gitlab.example.com/Troublemaker/avatar',
            'last_synchronization_date' => (new DateTimeImmutable())->getTimestamp(),
            'allow_artifact_closure'    => 0,
            'create_branch_prefix'      => 'lol-',
        ];
        return GroupLink::buildGroupLinkFromRow($row);
    }
}
