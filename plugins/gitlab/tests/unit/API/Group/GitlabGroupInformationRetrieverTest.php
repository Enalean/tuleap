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

namespace Tuleap\Gitlab\API\Group;

use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupPOSTRepresentation;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\Gitlab\Test\Stubs\GitlabClientWrapperStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitlabGroupInformationRetrieverTest extends TestCase
{
    private GitlabClientWrapperStub $gitlab_client;

    protected function setUp(): void
    {
        $this->gitlab_client = GitlabClientWrapperStub::buildWithNullResponse();
    }

    private function getGroup(): GitlabGroupApiDataRepresentation
    {
        $credentials                 = CredentialsTestBuilder::get()->build();
        $gitlab_group_representation = new GitlabGroupPOSTRepresentation(
            10,
            15,
            '154qa',
            'https://gitlab.example.com',
            true,
            'dev-'
        );

        $retriever = new GitlabGroupInformationRetriever($this->gitlab_client);

        return $retriever->getGitlabGroupFromGitlabApi($credentials, $gitlab_group_representation);
    }

    public function testItThrowsAnExceptionIfTheGitlabServerReturnNothing(): void
    {
        $this->expectException(GitlabResponseAPIException::class);
        $this->expectExceptionMessage('The query is not in error but the json content is empty. This is not expected.');
        $this->getGroup();
    }

    public function testItReturnsTheGitlabGroupData(): void
    {
        $gitlab_group_id = 15;

        $this->gitlab_client = GitlabClientWrapperStub::buildWithJson([
            'id'         => $gitlab_group_id,
            'name'       => 'nine-nine',
            'avatar_url' => 'https://avatar.example.com',
            'full_path'  => 'brookyln/nine-nine',
            'web_url'    => 'https://gitlab.example.com/nine-nine',
        ]);

        $result = $this->getGroup();

        self::assertSame($gitlab_group_id, $result->getGitlabGroupId());
        self::assertSame('nine-nine', $result->getName());
        self::assertSame('https://avatar.example.com', $result->getAvatarUrl());
        self::assertSame('brookyln/nine-nine', $result->getFullPath());
        self::assertSame('https://gitlab.example.com/nine-nine', $result->getWebUrl());
    }
}
