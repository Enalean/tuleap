<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Gitlab\API\Tag;

use Tuleap\Gitlab\API\GitlabResponseAPIException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class GitlabTagTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsATagRepresentation(): void
    {
        $response = [
            'name' => 'v1.0.2',
            'message' => 'Message from tag',
            'commit' => [
                'id' => 'commit_sha1',
            ],
        ];

        $gitlab_tap_api = GitlabTag::buildFromAPIResponse($response);

        self::assertSame('v1.0.2', $gitlab_tap_api->getName());
        self::assertSame('Message from tag', $gitlab_tap_api->getMessage());
        self::assertSame('commit_sha1', $gitlab_tap_api->getCommitSha1());
    }

    public function testItThrowsAnExceptionIfNameIsMissingRepresentation(): void
    {
        $response = [
            'message' => 'Message from tag',
            'commit' => [
                'id' => 'commit_sha1',
            ],
        ];

        $this->expectException(GitlabResponseAPIException::class);

        GitlabTag::buildFromAPIResponse($response);
    }

    public function testItThrowsAnExceptionIfMessageIsMissingRepresentation(): void
    {
        $response = [
            'name' => 'v1.0.2',
            'commit' => [
                'id' => 'commit_sha1',
            ],
        ];

        $this->expectException(GitlabResponseAPIException::class);

        GitlabTag::buildFromAPIResponse($response);
    }

    public function testItThrowsAnExceptionIfCommitIsMissingRepresentation(): void
    {
        $response = [
            'name' => 'v1.0.2',
            'message' => 'Message from tag',
        ];

        $this->expectException(GitlabResponseAPIException::class);

        GitlabTag::buildFromAPIResponse($response);
    }

    public function testItThrowsAnExceptionIfCommitIdIsMissingRepresentation(): void
    {
        $response = [
            'name' => 'v1.0.2',
            'message' => 'Message from tag',
            'commit' => [],
        ];

        $this->expectException(GitlabResponseAPIException::class);

        GitlabTag::buildFromAPIResponse($response);
    }
}
