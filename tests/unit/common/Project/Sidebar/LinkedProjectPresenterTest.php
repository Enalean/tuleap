<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Project\Sidebar;

use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CheckProjectAccessStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LinkedProjectPresenterTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testItBuildsFromLinkedProject(): void
    {
        \ForgeConfig::set('sys_default_domain', 'example.com');
        $user      = UserTestBuilder::aUser()->build();
        $project   = ProjectTestBuilder::aProject()
            ->withUnixName('red-team')
            ->withPublicName('Red Team')
            ->build();
        $presenter = LinkedProjectPresenter::fromLinkedProject(
            LinkedProject::fromProject(CheckProjectAccessStub::withValidAccess(), $project, $user)
        );
        self::assertSame('Red Team', $presenter->name);
        self::assertSame('https://example.com/projects/red-team', $presenter->href);
    }
}
