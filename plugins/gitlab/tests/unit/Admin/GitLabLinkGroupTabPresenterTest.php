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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Gitlab\Admin;

use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class GitLabLinkGroupTabPresenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testWithInactiveState(): void
    {
        $project = ProjectTestBuilder::aProject()->withUnixName('tuleap-gitlab')->build();

        $presenter = GitLabLinkGroupTabPresenter::withInactiveState($project);

        self::assertEquals('/plugins/git/tuleap-gitlab/administration/gitlab/', $presenter->getUrl());
        self::assertEquals('GitLab Group Link', $presenter->getPaneName());
        self::assertFalse($presenter->isActive());
    }

    public function testWithActiveState(): void
    {
        $project = ProjectTestBuilder::aProject()->withUnixName('tuleap-gitlab')->build();

        $presenter = GitLabLinkGroupTabPresenter::withActiveState($project);

        self::assertEquals('/plugins/git/tuleap-gitlab/administration/gitlab/', $presenter->getUrl());
        self::assertEquals('GitLab Group Link', $presenter->getPaneName());
        self::assertTrue($presenter->isActive());
    }
}
