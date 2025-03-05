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

namespace Tuleap\Project\Admin\ProjectMembers;

use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\GlobalLanguageMock;
use Tuleap\InviteBuddy\InvitationTestBuilder;
use Tuleap\InviteBuddy\InviteBuddiesPresenter;
use Tuleap\InviteBuddy\InviteBuddiesPresenterBuilder;
use Tuleap\InviteBuddy\InviteBuddyConfiguration;
use Tuleap\InviteBuddy\PendingInvitationsForProjectRetrieverStub;
use Tuleap\Project\Admin\Invitations\CSRFSynchronizerTokenProvider;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ListOfPendingInvitationsPresenterBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    private \Project $project;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->build();
        $this->user    = UserTestBuilder::buildWithDefaults();

        $GLOBALS['Language']
            ->method('getText')
            ->with('system', 'datefmt')
            ->willReturn('d/m/Y H:i');
    }

    public function testNullIfFeatureIsNotEnabled(): void
    {
        $configuration = $this->createMock(InviteBuddyConfiguration::class);
        $configuration->method('isFeatureEnabled')->willReturn(false);

        $builder = new ListOfPendingInvitationsPresenterBuilder(
            $configuration,
            PendingInvitationsForProjectRetrieverStub::withoutInvitation(),
            new TlpRelativeDatePresenterBuilder(),
            $this->createMock(CSRFSynchronizerTokenProvider::class),
            $this->createMock(InviteBuddiesPresenterBuilder::class),
        );

        self::assertNull(
            $builder->getPendingInvitationsPresenter($this->project, $this->user)
        );
    }

    public function testNullIfNoPendingInvitations(): void
    {
        $configuration = $this->createMock(InviteBuddyConfiguration::class);
        $configuration->method('isFeatureEnabled')->willReturn(true);

        $builder = new ListOfPendingInvitationsPresenterBuilder(
            $configuration,
            PendingInvitationsForProjectRetrieverStub::withoutInvitation(),
            new TlpRelativeDatePresenterBuilder(),
            $this->createMock(CSRFSynchronizerTokenProvider::class),
            $this->createMock(InviteBuddiesPresenterBuilder::class),
        );

        self::assertNull(
            $builder->getPendingInvitationsPresenter($this->project, $this->user)
        );
    }

    public function testWithPendingInvitations(): void
    {
        $configuration = $this->createMock(InviteBuddyConfiguration::class);
        $configuration->method('isFeatureEnabled')->willReturn(true);

        $token          = CSRFSynchronizerTokenStub::buildSelf();
        $token_provider = $this->createMock(CSRFSynchronizerTokenProvider::class);
        $token_provider->method('getCSRF')->willReturn($token);

        $invite_buddies_presenter_builder = $this->createMock(InviteBuddiesPresenterBuilder::class);
        $invite_buddies_presenter_builder
            ->method('build')
            ->willReturn($this->createMock(InviteBuddiesPresenter::class));

        $builder = new ListOfPendingInvitationsPresenterBuilder(
            $configuration,
            PendingInvitationsForProjectRetrieverStub::with(
                InvitationTestBuilder::aSentInvitation(1)->to('jane@example.com')->withCreatedOn(1234567890)->build(),
                InvitationTestBuilder::aSentInvitation(2)->to('john@example.com')->withCreatedOn(2345678901)->build(),
            ),
            new TlpRelativeDatePresenterBuilder(),
            $token_provider,
            $invite_buddies_presenter_builder
        );

        $presenter = $builder->getPendingInvitationsPresenter($this->project, $this->user);
        self::assertEquals('jane@example.com', $presenter->invitations[0]->email);
        self::assertEquals('john@example.com', $presenter->invitations[1]->email);
        self::assertEquals($token->getToken(), $presenter->csrf->getToken());
    }

    public function testItGroupsInvitationByEmailAndKeepTheNewestOne(): void
    {
        $configuration = $this->createMock(InviteBuddyConfiguration::class);
        $configuration->method('isFeatureEnabled')->willReturn(true);

        $token_provider = $this->createMock(CSRFSynchronizerTokenProvider::class);
        $token_provider->method('getCSRF')->willReturn(CSRFSynchronizerTokenStub::buildSelf());

        $invite_buddies_presenter_builder = $this->createMock(InviteBuddiesPresenterBuilder::class);
        $invite_buddies_presenter_builder
            ->method('build')
            ->willReturn($this->createMock(InviteBuddiesPresenter::class));

        $builder = new ListOfPendingInvitationsPresenterBuilder(
            $configuration,
            PendingInvitationsForProjectRetrieverStub::with(
                InvitationTestBuilder::aSentInvitation(1)->to('jane@example.com')->withCreatedOn(1234567890)->build(),
                InvitationTestBuilder::aSentInvitation(1)->to('john@example.com')->withCreatedOn(1234567891)->build(),
                InvitationTestBuilder::aSentInvitation(2)->to('jane@example.com')->withCreatedOn(2345678901)->build(),
            ),
            new TlpRelativeDatePresenterBuilder(),
            $token_provider,
            $invite_buddies_presenter_builder,
        );

        $presenter = $builder->getPendingInvitationsPresenter($this->project, $this->user);
        self::assertCount(2, $presenter->invitations);
        self::assertEquals('john@example.com', $presenter->invitations[0]->email);
        self::assertEquals('jane@example.com', $presenter->invitations[1]->email);
        self::assertTrue($presenter->invitations[0]->date->date < $presenter->invitations[1]->date->date);
    }
}
