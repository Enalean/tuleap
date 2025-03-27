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

namespace Tuleap\InviteBuddy;

use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class InvitationCleanerTest extends TestCase
{
    use TemporaryTestDirectory;
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    private const JANE_ID = 101;
    private const JOHN_ID = 102;

    private \PFUser $jane;
    private \PFUser $john;
    /**
     * @var InvitationInstrumentation&\PHPUnit\Framework\MockObject\MockObject
     */
    private $invitation_instrumentation;

    protected function setUp(): void
    {
        \ForgeConfig::set('sys_noreply', 'noreply@example.com');
        \ForgeConfig::set('sys_name', 'Acme Corp');

        $GLOBALS['Language']
            ->method('getContent')
            ->with('mail/html_template', 'en_US', null, '.php')
            ->willReturn(__DIR__ . '/../../../../site-content/en_US/mail/html_template.php');
        $GLOBALS['Language']
            ->method('getText')
            ->with('system', 'datefmt_short')
            ->willReturn('d/m/Y');

        $this->jane = UserTestBuilder::aUser()
            ->withId(self::JANE_ID)
            ->withRealName('Jane Doe')
            ->withEmail('jane@example.com')
            ->build();

        $this->john = UserTestBuilder::aUser()
            ->withId(self::JOHN_ID)
            ->withRealName('John McClane')
            ->withEmail('john@example.com')
            ->build();

        // needed for \Codendi_Mail internals
        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->method('getAllUsersByEmail')->willReturnMap(
            [
                [$this->jane->getEmail(), [$this->jane]],
                [$this->john->getEmail(), [$this->john]],
            ]
        );
        \UserManager::setInstance($user_manager);

        $this->invitation_instrumentation = $this->createMock(InvitationInstrumentation::class);
    }

    protected function tearDown(): void
    {
        \UserManager::clearInstance();
    }

    public function testDoesNotSendNotificationsIfNothingIsPurged(): void
    {
        $purger              = InvitationPurgerStub::withoutAnyPurgedInvitations();
        $captured_sent_mails = [];

        $cleaner = new InvitationCleaner(
            $purger,
            new LocaleSwitcher(),
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            static function (\Codendi_Mail $mail) use (&$captured_sent_mails): void {
                $captured_sent_mails[] = $mail;
            },
            RetrieveUserByIdStub::withNoUser(),
            ProjectByIDFactoryStub::buildWithoutProject(),
            $this->invitation_instrumentation,
        );

        $this->invitation_instrumentation->expects(self::never())->method('incrementExpiredInvitations');

        $cleaner->cleanObsoleteInvitations(new \DateTimeImmutable());

        self::assertTrue($purger->hasBeenCalled());
        self::assertEmpty($captured_sent_mails);
    }

    public function testItSendsAnEmailToTheUsersWhoInvited(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(111)
            ->withPublicName('Gotham City')
            ->build();

        $purger = InvitationPurgerStub::withPurgedInvitations(
            InvitationTestBuilder::aSentInvitation(1)
                ->from(self::JANE_ID)
                ->to('superman@example.com')
                ->withCreatedOn(1234567890)
                ->build(),
            InvitationTestBuilder::aSentInvitation(2)
                ->from(self::JOHN_ID)
                ->to('batman@example.com')
                ->toProjectId(111)
                ->withCreatedOn(1234567890)
                ->build(),
        );
        /**
         * @var \Codendi_Mail[] $captured_sent_mails
         */
        $captured_sent_mails = [];

        $cleaner = new InvitationCleaner(
            $purger,
            new LocaleSwitcher(),
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            static function (\Codendi_Mail $mail) use (&$captured_sent_mails): void {
                $captured_sent_mails[] = $mail;
            },
            RetrieveUserByIdStub::withUsers($this->jane, $this->john),
            ProjectByIDFactoryStub::buildWith($project),
            $this->invitation_instrumentation,
        );

        $this->invitation_instrumentation->expects($this->once())->method('incrementExpiredInvitations')->with(2);

        $cleaner->cleanObsoleteInvitations(new \DateTimeImmutable());

        self::assertTrue($purger->hasBeenCalled());
        self::assertCount(2, $captured_sent_mails);

        self::assertStringContainsString('Jane Doe', $captured_sent_mails[0]->getBodyText());
        self::assertTrue((bool) preg_match('%Invitation sent on \d\d/02/2009%', $captured_sent_mails[0]->getBodyText()));
        self::assertStringContainsString('superman@example.com', $captured_sent_mails[0]->getBodyText());

        self::assertStringContainsString('John McClane', $captured_sent_mails[1]->getBodyText());
        self::assertTrue((bool) preg_match('%Invitation sent for project Gotham City on \d\d/02/2009%', $captured_sent_mails[1]->getBodyText()));
        self::assertStringContainsString('batman@example.com', $captured_sent_mails[1]->getBodyText());
    }

    public function testItGroupsInvitationsInOneEmail(): void
    {
        $purger = InvitationPurgerStub::withPurgedInvitations(
            InvitationTestBuilder::aSentInvitation(1)
                ->from(self::JANE_ID)
                ->to('superman@example.com')
                ->build(),
            InvitationTestBuilder::aSentInvitation(2)
                ->from(self::JANE_ID)
                ->to('batman@example.com')
                ->build(),
        );
        /**
         * @var \Codendi_Mail[] $captured_sent_mails
         */
        $captured_sent_mails = [];

        $cleaner = new InvitationCleaner(
            $purger,
            new LocaleSwitcher(),
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            static function (\Codendi_Mail $mail) use (&$captured_sent_mails): void {
                $captured_sent_mails[] = $mail;
            },
            RetrieveUserByIdStub::withUsers($this->jane, $this->john),
            ProjectByIDFactoryStub::buildWithoutProject(),
            $this->invitation_instrumentation,
        );

        $this->invitation_instrumentation->expects($this->once())->method('incrementExpiredInvitations')->with(2);

        $cleaner->cleanObsoleteInvitations(new \DateTimeImmutable());

        self::assertTrue($purger->hasBeenCalled());
        self::assertCount(1, $captured_sent_mails);

        self::assertStringContainsString('Jane Doe', $captured_sent_mails[0]->getBodyText());
        self::assertStringContainsString('superman@example.com', $captured_sent_mails[0]->getBodyText());
        self::assertStringContainsString('batman@example.com', $captured_sent_mails[0]->getBodyText());
    }

    public function testItDoesNotSendANotificationForInvitationsThatWereNotSent(): void
    {
        $purger = InvitationPurgerStub::withPurgedInvitations(
            InvitationTestBuilder::anErrorInvitation(1)
                ->from(self::JANE_ID)
                ->to('superman@example.com')
                ->build(),
            InvitationTestBuilder::aCreatingInvitation(2)
                ->from(self::JANE_ID)
                ->to('batman@example.com')
                ->build(),
        );
        /**
         * @var \Codendi_Mail[] $captured_sent_mails
         */
        $captured_sent_mails = [];

        $cleaner = new InvitationCleaner(
            $purger,
            new LocaleSwitcher(),
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            static function (\Codendi_Mail $mail) use (&$captured_sent_mails): void {
                $captured_sent_mails[] = $mail;
            },
            RetrieveUserByIdStub::withUsers($this->jane, $this->john),
            ProjectByIDFactoryStub::buildWithoutProject(),
            $this->invitation_instrumentation,
        );

        $this->invitation_instrumentation->expects($this->once())->method('incrementExpiredInvitations')->with(2);

        $cleaner->cleanObsoleteInvitations(new \DateTimeImmutable());

        self::assertTrue($purger->hasBeenCalled());
        self::assertCount(0, $captured_sent_mails);
    }

    public function testItDoesNotSendANotificationForInvitationsThatWereSentForAnExistingUser(): void
    {
        $purger = InvitationPurgerStub::withPurgedInvitations(
            InvitationTestBuilder::aSentInvitation(1)
                ->from(self::JANE_ID)
                ->to(201)
                ->build(),
            InvitationTestBuilder::aSentInvitation(2)
                ->from(self::JANE_ID)
                ->to(202)
                ->build(),
        );
        /**
         * @var \Codendi_Mail[] $captured_sent_mails
         */
        $captured_sent_mails = [];

        $cleaner = new InvitationCleaner(
            $purger,
            new LocaleSwitcher(),
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            static function (\Codendi_Mail $mail) use (&$captured_sent_mails): void {
                $captured_sent_mails[] = $mail;
            },
            RetrieveUserByIdStub::withUsers($this->jane, $this->john),
            ProjectByIDFactoryStub::buildWithoutProject(),
            $this->invitation_instrumentation,
        );

        $this->invitation_instrumentation->expects($this->once())->method('incrementExpiredInvitations')->with(2);

        $cleaner->cleanObsoleteInvitations(new \DateTimeImmutable());

        self::assertTrue($purger->hasBeenCalled());
        self::assertCount(0, $captured_sent_mails);
    }
}
