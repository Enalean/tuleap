<?php
/**
 * Copyright Enalean (c) 2014 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Git\SystemEvents;

use ColinODell\PsrTestLogger\TestLogger;
use Git_UserAccountManager;
use Git_UserSynchronisationException;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use SystemEvent;
use SystemEvent_GIT_EDIT_SSH_KEYS;
use Tuleap\Git\Tests\Stub\Gitolite\SSHKey\DumperStub;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;
use UserNotExistException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SystemEvent_GIT_EDIT_SSH_KEYSTest extends TestCase
{
    use GlobalLanguageMock;

    private Git_UserAccountManager&MockObject $user_account_manager;
    private TestLogger $logger;
    private PFUser&MockObject $user;
    private UserManager&MockObject $user_manager;

    protected function setUp(): void
    {
        $this->user                 = $this->createMock(PFUser::class);
        $this->user_manager         = $this->createMock(UserManager::class);
        $this->user_account_manager = $this->createMock(Git_UserAccountManager::class);
        $this->logger               = new TestLogger();

        $this->user_manager->method('getUserById')->willReturnCallback(fn(int $id) => match ($id) {
            105     => $this->user,
            default => null,
        });
    }

    public function testItLogsAnErrorIfNoUserIsPassed(): void
    {
        $event = new SystemEvent_GIT_EDIT_SSH_KEYS('', '', '', '', '', '', '', '', '', '');
        $event->injectDependencies(
            $this->user_manager,
            DumperStub::build(),
            $this->user_account_manager,
            $this->logger
        );

        $this->expectException(UserNotExistException::class);

        $event->process();
    }

    public function testItLogsAnErrorIfUserIsInvalid(): void
    {
        $event = new SystemEvent_GIT_EDIT_SSH_KEYS('', '', '', 'me', '', '', '', '', '', '');
        $event->injectDependencies(
            $this->user_manager,
            DumperStub::build(),
            $this->user_account_manager,
            $this->logger
        );

        $this->expectException(UserNotExistException::class);

        $event->process();
    }

    public function testItTransformsEmptyKeyStringIntoArrayBeforeSendingToGitUserManager(): void
    {
        $original_keys = [];
        $new_keys      = [];

        $event = new SystemEvent_GIT_EDIT_SSH_KEYS('', '', '', '105::', '', '', '', '', '', '');
        $event->injectDependencies(
            $this->user_manager,
            DumperStub::build(),
            $this->user_account_manager,
            $this->logger
        );

        $this->user->method('getAuthorizedKeysArray')->willReturn($new_keys);

        $this->user_account_manager->expects(self::once())->method('synchroniseSSHKeys')->with($original_keys, $new_keys, $this->user);

        $event->process();
        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testItTransformsNonEmptyKeyStringIntoArrayBeforeSendingToGitUserManager(): void
    {
        $new_keys      = [];
        $original_keys = [
            'abcdefg',
            'wxyz',
        ];

        $event = new SystemEvent_GIT_EDIT_SSH_KEYS('', '', '', '105::' . 'abcdefg' . PFUser::SSH_KEY_SEPARATOR . 'wxyz', '', '', '', '', '', '');
        $event->injectDependencies(
            $this->user_manager,
            DumperStub::build(),
            $this->user_account_manager,
            $this->logger
        );

        $this->user->method('getAuthorizedKeysArray')->willReturn($new_keys);

        $this->user_account_manager->expects(self::once())->method('synchroniseSSHKeys')->with($original_keys, $new_keys, $this->user);

        $event->process();
        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testItWarnsAdminsWhenSSHKeySynchFails(): void
    {
        $event = new SystemEvent_GIT_EDIT_SSH_KEYS('', '', '', '105::', '', '', '', '', '', '');
        $event->injectDependencies(
            $this->user_manager,
            DumperStub::build(),
            $this->user_account_manager,
            $this->logger
        );

        $this->user->method('getAuthorizedKeysArray')->willReturn([]);
        $this->user->method('getUserName');

        $this->user_account_manager->method('synchroniseSSHKeys')->willThrowException(new Git_UserSynchronisationException());

        $event->process();

        self::assertEquals(SystemEvent::STATUS_WARNING, $event->getStatus());
    }
}
