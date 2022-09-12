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

use Tuleap\Gitlab\API\Group\GitlabGroupApiDataRepresentation;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class NewGroupTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROJECT_ID      = 136;
    private const GITLAB_GROUP_ID = 84;
    private const GROUP_NAME      = 'foldy-logarithm';
    private const FULL_PATH       = 'Saltigradae/foldy-logarithm';
    private const WEB_URL         = 'https://gitlab.example.com/Saltigradae/foldy-logarithm';
    private const AVATAR_URL      = 'https://gitlab.example.com/avatar';

    public function testItBuildsFromAPIRepresentationAndProject(): void
    {
        $api_group = GitlabGroupApiDataRepresentation::buildGitlabGroupFromApi([
            'id'         => self::GITLAB_GROUP_ID,
            'name'       => self::GROUP_NAME,
            'full_path'  => self::FULL_PATH,
            'web_url'    => self::WEB_URL,
            'avatar_url' => self::AVATAR_URL,
        ]);

        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $group = NewGroup::fromAPIRepresentationAndProject($api_group, $project);

        self::assertSame(self::GITLAB_GROUP_ID, $group->gitlab_group_id);
        self::assertSame(self::PROJECT_ID, $group->project_id);
        self::assertSame(self::GROUP_NAME, $group->name);
        self::assertSame(self::FULL_PATH, $group->full_path);
        self::assertSame(self::WEB_URL, $group->web_url);
        self::assertSame(self::AVATAR_URL, $group->avatar_url);
        self::assertNotSame(0, $group->last_synchronization_date->getTimestamp());
        self::assertFalse($group->allow_artifact_closure);
        self::assertNull($group->prefix_branch_name);
    }
}
