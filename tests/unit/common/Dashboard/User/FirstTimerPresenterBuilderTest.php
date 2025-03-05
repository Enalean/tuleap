<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Dashboard\User;

use Tuleap\Config\ConfigurationVariables;
use Tuleap\ForgeConfigSandbox;
use Tuleap\InviteBuddy\InvitationTestBuilder;
use Tuleap\InviteBuddy\UsedInvitationRetrieverStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class FirstTimerPresenterBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testHappyPath(): void
    {
        \ForgeConfig::set(ConfigurationVariables::NAME, 'Tuleap');

        $project_admin = UserTestBuilder::aUser()
            ->withId(102)
            ->withRealName('Agent Smith')
            ->build();

        $invitee = UserTestBuilder::aUser()
            ->withId(103)
            ->withIsFirstTimer(true)
            ->withRealName('Thomas Neo Anderson')
            ->build();

        $builder = new FirstTimerPresenterBuilder(
            UsedInvitationRetrieverStub::withUsedInvitation(
                InvitationTestBuilder::aUsedInvitation(1)
                    ->from($project_admin->getId())
                    ->to('jdoe@example.com')
                    ->build(),
            ),
            RetrieveUserByIdStub::withUser($project_admin),
            ProvideUserAvatarUrlStub::build(),
        );

        $presenter = $builder->buildPresenter($invitee);

        self::assertEquals('Agent Smith', $presenter->invited_by_user->real_name);
        self::assertEquals('Thomas Neo Anderson', $presenter->real_name);
    }

    public function testWithoutInvitation(): void
    {
        \ForgeConfig::set(ConfigurationVariables::NAME, 'Tuleap');

        $invitee = UserTestBuilder::aUser()
            ->withId(103)
            ->withIsFirstTimer(true)
            ->withRealName('Thomas Neo Anderson')
            ->build();

        $builder = new FirstTimerPresenterBuilder(
            UsedInvitationRetrieverStub::withoutInvitation(),
            RetrieveUserByIdStub::withNoUser(),
            ProvideUserAvatarUrlStub::build(),
        );

        $presenter = $builder->buildPresenter($invitee);

        self::assertEquals(null, $presenter->invited_by_user);
        self::assertEquals('Thomas Neo Anderson', $presenter->real_name);
    }

    public function testNullWhenUserIsNotAFirstTimer(): void
    {
        $builder = new FirstTimerPresenterBuilder(
            UsedInvitationRetrieverStub::withoutInvitation(),
            RetrieveUserByIdStub::withNoUser(),
            ProvideUserAvatarUrlStub::build(),
        );

        self::assertNull($builder->buildPresenter(UserTestBuilder::buildWithDefaults()));
    }
}
