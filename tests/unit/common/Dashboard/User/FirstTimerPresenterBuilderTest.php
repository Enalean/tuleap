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

use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\ForgeConfigSandbox;
use Tuleap\InviteBuddy\Invitation;
use Tuleap\InviteBuddy\InvitationByTokenRetrieverStub;
use Tuleap\InviteBuddy\PrefixTokenInvitation;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;

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
            ->withRealName('Thomas Neo Anderson')
            ->build();

        $identifier = new PrefixedSplitTokenSerializer(new PrefixTokenInvitation());

        $builder = new FirstTimerPresenterBuilder(
            InvitationByTokenRetrieverStub::withMatchingInvitation(
                new Invitation('jdoe@example.com', $project_admin->getId())
            ),
            $identifier,
            RetrieveUserByIdStub::withUser($project_admin),
        );

        $presenter = $builder->buildPresenter(
            HTTPRequestBuilder::get()
                ->withUser($invitee)
                ->withParam(
                    'invitation-token',
                    $identifier->getIdentifier(
                        new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString())
                    )->getString()
                )
                ->build()
        );

        self::assertEquals('Agent Smith', $presenter->invited_by_user->real_name);
        self::assertEquals('Thomas Neo Anderson', $presenter->real_name);
    }

    public function testNullWhenTokenIsNotInTheRequest(): void
    {
        $builder = new FirstTimerPresenterBuilder(
            InvitationByTokenRetrieverStub::withoutMatchingInvitation(),
            new PrefixedSplitTokenSerializer(new PrefixTokenInvitation()),
            RetrieveUserByIdStub::withNoUser(),
        );

        self::assertNull($builder->buildPresenter(HTTPRequestBuilder::get()->build()));
    }

    public function testExceptionWhenTokenIsInvalid(): void
    {
        $builder = new FirstTimerPresenterBuilder(
            InvitationByTokenRetrieverStub::withoutMatchingInvitation(),
            new PrefixedSplitTokenSerializer(new PrefixTokenInvitation()),
            RetrieveUserByIdStub::withNoUser(),
        );

        $this->expectException(ForbiddenException::class);

        $builder->buildPresenter(
            HTTPRequestBuilder::get()
                ->withParam('invitation-token', 'not-a-valid-token')
                ->build()
        );
    }

    public function testExceptionWhenInvitationIsNotFound(): void
    {
        $identifier = new PrefixedSplitTokenSerializer(new PrefixTokenInvitation());

        $builder = new FirstTimerPresenterBuilder(
            InvitationByTokenRetrieverStub::withoutMatchingInvitation(),
            $identifier,
            RetrieveUserByIdStub::withNoUser(),
        );

        $this->expectException(ForbiddenException::class);

        $builder->buildPresenter(
            HTTPRequestBuilder::get()
                ->withParam(
                    'invitation-token',
                    $identifier->getIdentifier(
                        new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString())
                    )->getString()
                )
                ->build()
        );
    }
}
