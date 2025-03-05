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

namespace Tuleap\Gitlab\Admin;

use Tuleap\Gitlab\Test\Builder\GroupLinkBuilder;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\URIInterfaceBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LinkedGroupPresenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private const PROJECT_NAME                  = 'impierceable';
    private const GROUP_NAME                    = 'Nonannuitant';
    private const GROUP_PATH                    = 'cottonbush/nonannuitant';
    private const GITLAB_URI                    = 'https://gitlab.example.com';
    private const NUMBER_OF_INTEGRATED_PROJECTS = 6;

    public function testItBuilds(): void
    {
        $group_link = GroupLinkBuilder::aGroupLink(34)
            ->withName(self::GROUP_NAME)
            ->withFullPath(self::GROUP_PATH)
            ->withNoBranchPrefix()
            ->build();
        $project    = ProjectTestBuilder::aProject()->withPublicName(self::PROJECT_NAME)->build();
        $presenter  = new LinkedGroupPresenter(
            new GitLabLinkGroupPanePresenter($project, []),
            $project,
            $group_link,
            URIInterfaceBuilder::fromString(self::GITLAB_URI),
            self::NUMBER_OF_INTEGRATED_PROJECTS
        );

        self::assertSame(self::PROJECT_NAME, $presenter->current_project_label);
        self::assertSame(self::NUMBER_OF_INTEGRATED_PROJECTS, $presenter->number_of_integrated_projects_in_last_sync);
        self::assertEqualsIgnoringCase('N', $presenter->first_letter_of_group_name);
        self::assertSame(self::GITLAB_URI, $presenter->gitlab_url);
        self::assertFalse($presenter->has_branch_prefix);
    }
}
