<?php
/**
 * Copyright Enalean (c) 2014. All rights reserved.
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

require_once dirname(__FILE__).'/../bootstrap.php';

class SystemEvent_GIT_EDIT_SSH_KEYSTest extends TuleapTestCase {

    private $event;
    private $user_account_manager;
    private $logger;
    private $user;
    private $user_manager;
    private $sshkey_dumper;

    public function setUp() {
        parent::setUp();

        $this->user                 = mock('PFUser');
        $this->user_manager         = mock('UserManager');
        $this->sshkey_dumper        = mock('Git_Gitolite_SSHKeyDumper');
        $this->user_account_manager = mock('Git_UserAccountManager');
        $this->system_event_manager = mock('Git_SystemEventManager');
        $this->logger               = mock('BackendLogger');

        stub($this->user_manager)->getUserById(105)->returns($this->user);
    }

    public function testItLogsAnErrorIfNoUserIsPassed() {
        $event = new SystemEvent_GIT_EDIT_SSH_KEYS('', '', '', '', '', '', '', '', '', '');
        $event->injectDependencies(
            $this->user_manager,
            $this->sshkey_dumper,
            $this->user_account_manager,
            $this->system_event_manager,
            $this->logger
        );

        $this->expectException('UserNotExistException');

        $event->process();
    }

    public function testItLogsAnErrorIfUserIsInvalid() {
        $event = new SystemEvent_GIT_EDIT_SSH_KEYS('', '', '', 'me', '', '', '', '', '', '');
        $event->injectDependencies(
            $this->user_manager,
            $this->sshkey_dumper,
            $this->user_account_manager,
            $this->system_event_manager,
            $this->logger
        );

        $this->expectException('UserNotExistException');

        $event->process();
    }

    public function itTransformsEmptyKeyStringIntoArrayBeforeSendingToGitUserManager() {
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

        stub($this->user)->getAuthorizedKeysArray()->returns($new_keys);

        expect($this->logger)->error()->never();
        expect($this->user_account_manager)->synchroniseSSHKeys(
                $original_keys,
                $new_keys,
                $this->user
            )->once();

        $event->process();
    }

    public function itTransformsNonEmptyKeyStringIntoArrayBeforeSendingToGitUserManager() {
        $new_keys      = array();
        $original_keys = array(
            'abcdefg',
            'wxyz',
        );

        $event = new SystemEvent_GIT_EDIT_SSH_KEYS('', '', '', '105::'.'abcdefg'.PFUser::SSH_KEY_SEPARATOR.'wxyz', '', '', '', '', '', '');
        $event->injectDependencies(
            $this->user_manager,
            $this->sshkey_dumper,
            $this->user_account_manager,
            $this->system_event_manager,
            $this->logger
        );


        stub($this->user)->getAuthorizedKeysArray()->returns($new_keys);

        expect($this->logger)->error()->never();
        expect($this->user_account_manager)->synchroniseSSHKeys(
                $original_keys,
                $new_keys,
                $this->user
            )->once();

         $event->process();
    }

    public function itWarnsAdminsWhenSSHKeySynchFails() {
        $event = new SystemEvent_GIT_EDIT_SSH_KEYS('', '', '', '105::', '', '', '', '', '', '');
        $event->injectDependencies(
            $this->user_manager,
            $this->sshkey_dumper,
            $this->user_account_manager,
            $this->system_event_manager,
            $this->logger
        );

        stub($this->user)->getAuthorizedKeysArray()->returns([]);

        $this->user_account_manager->throwOn('synchroniseSSHKeys', new Git_UserSynchronisationException());

        $event->process();

        $this->assertEqual($event->getStatus(), SystemEvent::STATUS_WARNING);
    }
}
