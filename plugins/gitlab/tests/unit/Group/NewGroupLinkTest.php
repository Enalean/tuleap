<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Group;

use DateTimeImmutable;
use Tuleap\Gitlab\API\Group\GitlabGroupApiDataRepresentation;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NewGroupLinkTest extends TestCase
{
    private const int PROJECT_ID                     = 136;
    private const int GITLAB_GROUP_ID                = 84;
    private const string GROUP_NAME                  = 'foldy-logarithm';
    private const string FULL_PATH                   = 'Saltigradae/foldy-logarithm';
    private const string WEB_URL                     = 'https://gitlab.example.com/Saltigradae/foldy-logarithm';
    private const string AVATAR_URL                  = 'https://gitlab.example.com/avatar';
    private const int LAST_SYNCHRONIZATION_TIMESTAMP = 1658457229;
    private const string BRANCH_PREFIX               = 'dev-';

    public function testItBuildsFromAPIRepresentation(): void
    {
        $api_group = GitlabGroupApiDataRepresentation::buildGitlabGroupFromApi([
            'id'         => self::GITLAB_GROUP_ID,
            'name'       => self::GROUP_NAME,
            'full_path'  => self::FULL_PATH,
            'web_url'    => self::WEB_URL,
            'avatar_url' => self::AVATAR_URL,
        ]);

        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $last_synchronization_date = new DateTimeImmutable('@' . self::LAST_SYNCHRONIZATION_TIMESTAMP);

        $group = NewGroupLink::fromAPIRepresentation(
            $api_group,
            $project,
            $last_synchronization_date,
            true,
            self::BRANCH_PREFIX
        );

        self::assertSame(self::GITLAB_GROUP_ID, $group->gitlab_group_id);
        self::assertSame(self::PROJECT_ID, $group->project_id);
        self::assertSame(self::GROUP_NAME, $group->name);
        self::assertSame(self::FULL_PATH, $group->full_path);
        self::assertSame(self::WEB_URL, $group->web_url);
        self::assertSame(self::AVATAR_URL, $group->avatar_url);
        self::assertSame(self::LAST_SYNCHRONIZATION_TIMESTAMP, $group->last_synchronization_date->getTimestamp());
        self::assertTrue($group->allow_artifact_closure);
        self::assertSame(self::BRANCH_PREFIX, $group->prefix_branch_name);
    }
}
