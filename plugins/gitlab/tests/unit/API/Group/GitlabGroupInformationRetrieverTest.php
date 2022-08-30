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

final class GitlabGroupInformationRetrieverTest extends TestCase
{
    public function testItThrowsAnExceptionIfTheGitlabServerReturnNothing(): void
    {
        $gitlab_group_information_retriever = new GitlabGroupInformationRetriever(
            GitlabClientWrapperStub::buildWithNullResponse()
        );
        self::expectException(GitlabResponseAPIException::class);
        self::expectExceptionMessage("The query is not in error but the json content is empty. This is not expected.");

        $credential                  = CredentialsTestBuilder::get()->build();
        $gitlab_group_representation = new GitlabGroupPOSTRepresentation(
            10,
            15,
            "154qa",
            "https://gitlab.example.com"
        );
        $gitlab_group_information_retriever->getGitlabGroupFromGitlabApi($credential, $gitlab_group_representation);
    }

    public function testItReturnsTheGitlabGroupData(): void
    {
        $gitlab_group_id          = 15;
        $group_data               = [];
        $group_data['id']         = $gitlab_group_id;
        $group_data['name']       = "nine-nine";
        $group_data['avatar_url'] = "https://avatar.example.com";
        $group_data['full_path']  = "brookyln/nine-nine";
        $group_data['web_url']    = "https://gitlab.example.com/nine-nine";

        $gitlab_group_information_retriever = new GitlabGroupInformationRetriever(
            GitlabClientWrapperStub::buildWithJson($group_data)
        );

        $credential = CredentialsTestBuilder::get()->build();

        $gitlab_group_representation = new GitlabGroupPOSTRepresentation(
            10,
            $gitlab_group_id,
            "154qa",
            "https://gitlab.example.com"
        );

        $result = $gitlab_group_information_retriever->getGitlabGroupFromGitlabApi($credential, $gitlab_group_representation);

        self::assertSame($gitlab_group_id, $result->getGitlabGroupId());
        self::assertSame("nine-nine", $result->getName());
        self::assertSame("https://avatar.example.com", $result->getAvatarUrl());
        self::assertSame("brookyln/nine-nine", $result->getFullPath());
        self::assertSame("https://gitlab.example.com/nine-nine", $result->getWebUrl());
    }
}
