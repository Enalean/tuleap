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

require_once __DIR__ . '/../bootstrap.php';

class SystemEvent_GIT_EDIT_SSH_KEYSTest extends \PHPUnit\Framework\TestCase
{

    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, \Tuleap\GlobalLanguageMock;
    private $user_account_manager;
    private $logger;
    private $user;
    private $user_manager;
    private $sshkey_dumper;

    protected function setUp() : void
    {
        parent::setUp();

        $this->user                 = \Mockery::spy(\PFUser::class);
        $this->user_manager         = \Mockery::spy(\UserManager::class);
        $this->sshkey_dumper        = \Mockery::spy(\Git_Gitolite_SSHKeyDumper::class);
        $this->user_account_manager = \Mockery::spy(\Git_UserAccountManager::class);
        $this->system_event_manager = \Mockery::spy(\Git_SystemEventManager::class);
        $this->logger               = \Mockery::spy(\Psr\Log\LoggerInterface::class);

        $this->user_manager->shouldReceive('getUserById')->with(105)->andReturns($this->user);
    }

    public function testItLogsAnErrorIfNoUserIsPassed() : void
    {
        $event = new SystemEvent_GIT_EDIT_SSH_KEYS('', '', '', '', '', '', '', '', '', '');
        $event->injectDependencies(
            $this->user_manager,
            $this->sshkey_dumper,
            $this->user_account_manager,
            $this->system_event_manager,
            $this->logger
        );

        $this->expectException(\UserNotExistException::class);

        $event->process();
    }

    public function testItLogsAnErrorIfUserIsInvalid() : void
    {
        $event = new SystemEvent_GIT_EDIT_SSH_KEYS('', '', '', 'me', '', '', '', '', '', '');
        $event->injectDependencies(
            $this->user_manager,
            $this->sshkey_dumper,
            $this->user_account_manager,
            $this->system_event_manager,
            $this->logger
        );

        $this->expectException(\UserNotExistException::class);

        $event->process();
    }

    public function testItTransformsEmptyKeyStringIntoArrayBeforeSendingToGitUserManager() : void
    {
        $original_keys = array();
        $new_keys = array();

        $event = new SystemEvent_GIT_EDIT_SSH_KEYS('', '', '', '105::', '', '', '', '', '', '');
        $event->injectDependencies(
            $this->user_manager,
            $this->sshkey_dumper,
            $this->user_account_manager,
            $this->system_event_manager,
            $this->logger
        );

        $this->user->shouldReceive('getAuthorizedKeysArray')->andReturns($new_keys);

        $this->logger->shouldReceive('error')->never();
        $this->user_account_manager->shouldReceive('synchroniseSSHKeys')->with($original_keys, $new_keys, $this->user)->once();

        $event->process();
    }

    public function testItTransformsNonEmptyKeyStringIntoArrayBeforeSendingToGitUserManager() : void
    {
        $new_keys      = array();
        $original_keys = array(
            'abcdefg',
            'wxyz',
        );

        $event = new SystemEvent_GIT_EDIT_SSH_KEYS('', '', '', '105::' . 'abcdefg' . PFUser::SSH_KEY_SEPARATOR . 'wxyz', '', '', '', '', '', '');
        $event->injectDependencies(
            $this->user_manager,
            $this->sshkey_dumper,
            $this->user_account_manager,
            $this->system_event_manager,
            $this->logger
        );

        $this->user->shouldReceive('getAuthorizedKeysArray')->andReturns($new_keys);

        $this->logger->shouldReceive('error')->never();
        $this->user_account_manager->shouldReceive('synchroniseSSHKeys')->with($original_keys, $new_keys, $this->user)->once();

         $event->process();
    }

    public function testItWarnsAdminsWhenSSHKeySynchFails() : void
    {
        $event = new SystemEvent_GIT_EDIT_SSH_KEYS('', '', '', '105::', '', '', '', '', '', '');
        $event->injectDependencies(
            $this->user_manager,
            $this->sshkey_dumper,
            $this->user_account_manager,
            $this->system_event_manager,
            $this->logger
        );

        $this->user->shouldReceive('getAuthorizedKeysArray')->andReturns([]);

        $this->user_account_manager->shouldReceive('synchroniseSSHKeys')->andThrows(new Git_UserSynchronisationException());

        $event->process();

        $this->assertEquals(SystemEvent::STATUS_WARNING, $event->getStatus());
    }
}
