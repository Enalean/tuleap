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
use Tuleap\Test\PHPUnit\TestCase;

/**
 * [{"id": 10, "name": "my_group", "full_path": "https://gitlab.example.com/path/full", "web_url": "https://gitlab.example.com/webur", "avatar_url": "https://gitlab.example.com/avatar"}]
 */
final class GitlabGroupApiDataRepresentationTest extends TestCase
{
    /**
     * @testWith [{"name": "my_group", "full_path": "https://gitlab.example.com/path/full", "web_url": "https://gitlab.example.com/webur", "avatar_url": "https://gitlab.example.com/avatar"}]
     *           [{"id": 10, "full_path": "https://gitlab.example.com/path/full", "web_url": "https://gitlab.example.com/webur", "avatar_url": "https://gitlab.example.com/avatar"}]
     *           [{"id": 10, "name": "my_group", "web_url": "https://gitlab.example.com/webur", "avatar_url": "https://gitlab.example.com/avatar"}]
     *           [{"id": 10, "name": "my_group", "full_path": "https://gitlab.example.com/path/full", "avatar_url": "https://gitlab.example.com/avatar"}]
     *           [{"id": 10, "name": "my_group", "full_path": "https://gitlab.example.com/path/full", "web_url": "https://gitlab.example.com/webur"}]
     *           [{"id": 10, "web_url": "https://gitlab.example.com/webur", "avatar_url": "https://gitlab.example.com/avatar"}]
     */
    public function testItThrowsExceptionIfMandatoryKeyIsMissing(array $group_data): void
    {
        self::expectException(GitlabResponseAPIException::class);
        self::expectExceptionMessage("Some keys are missing in the group Json. This is not expected. Aborting.");

        GitlabGroupApiDataRepresentation::buildGitlabGroupFromApi($group_data);
    }

    /**
     * @testWith [{"id": "10", "name": "my_group", "full_path": "https://gitlab.example.com/path/full", "web_url": "https://gitlab.example.com/webur", "avatar_url": "https://gitlab.example.com/avatar"}]
     *           [{"id": 10, "name": 10, "full_path": "https://gitlab.example.com/path/full", "web_url": "https://gitlab.example.com/webur", "avatar_url": "https://gitlab.example.com/avatar"}]
     *           [{"id": 10, "name": "my_group", "full_path": 12, "web_url": "https://gitlab.example.com/webur", "avatar_url": "https://gitlab.example.com/avatar"}]
     *           [{"id": 10, "name": "my_group", "full_path": "https://gitlab.example.com/path/full", "web_url": 15, "avatar_url": "https://gitlab.example.com/avatar"}]
     *           [{"id": 10, "name": "my_group", "full_path": "https://gitlab.example.com/path/full", "web_url": "https://gitlab.example.com/webur", "avatar_url": 20}]
     */
    public function testItThrowsExceptionIfMandatoryKeyHasNotTheRightType(array $group_data): void
    {
        self::expectException(GitlabResponseAPIException::class);
        self::expectExceptionMessage("Some keys haven't the expected types. This is not expected. Aborting.");

        GitlabGroupApiDataRepresentation::buildGitlabGroupFromApi($group_data);
    }

    public function testItReturnsTheGroupData(): void
    {
        $group_data               = [];
        $group_data['id']         = 102;
        $group_data['name']       = "nine-nine";
        $group_data['avatar_url'] = "https://avatar.example.com";
        $group_data['full_path']  = "brookyln/nine-nine";
        $group_data['web_url']    = "https://gitlab.example.com/nine-nine";

        $result = GitlabGroupApiDataRepresentation::buildGitlabGroupFromApi($group_data);

        self::assertSame(102, $result->getGitlabGroupId());
        self::assertSame("nine-nine", $result->getName());
        self::assertSame("https://avatar.example.com", $result->getAvatarUrl());
        self::assertSame("brookyln/nine-nine", $result->getFullPath());
        self::assertSame("https://gitlab.example.com/nine-nine", $result->getWebUrl());
    }
}
