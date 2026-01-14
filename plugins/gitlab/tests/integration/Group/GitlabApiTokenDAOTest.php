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
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Gitlab\Group\GroupLink;
use Tuleap\Gitlab\Group\Token\GroupLinkApiTokenDAO;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitlabApiTokenDAOTest extends TestIntegrationTestCase
{
    private GroupLinkApiTokenDAO $token_dao;

    private const int GROUP_LINK_ID    = 1;
    private const string TOKEN         = 'Oxt0ken1';
    private const string UPDATED_TOKEN = 'Oxt0ken1_update';

    #[\Override]
    protected function setUp(): void
    {
        $this->token_dao = new GroupLinkApiTokenDAO();
    }

    public function testCRUD(): void
    {
        $this->addTokenToGroupLink();
        $this->updateTokenOfGroupLink();
    }

    private function addTokenToGroupLink(): void
    {
        $this->token_dao->storeToken(self::GROUP_LINK_ID, new ConcealedString(self::TOKEN));

        self::assertTrue($this->token_dao->getTokenByGroupId(self::GROUP_LINK_ID)->isIdenticalTo(new ConcealedString(self::TOKEN)));
    }

    private function updateTokenOfGroupLink(): void
    {
        $this->token_dao->updateGitlabTokenOfGroupLink($this->buildGroupLink(), new ConcealedString(self::UPDATED_TOKEN));

        self::assertTrue($this->token_dao->getTokenByGroupId(self::GROUP_LINK_ID)->isIdenticalTo(new ConcealedString(self::UPDATED_TOKEN)));
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
