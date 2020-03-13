<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All Rights Reserved.
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

use Tuleap\Git\Gitolite\SSHKey\Dumper;
use Tuleap\Git\Gitolite\SSHKey\InvalidKeysCollector;

class SystemEvent_GIT_EDIT_SSH_KEYS extends SystemEvent
{
    public const NAME = 'GIT_EDIT_SSH_KEYS';

    /** @var UserManager */
    private $user_manager;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var Dumper */
    private $sshkey_dumper;

    /** @var Git_UserAccountManager */
    private $git_user_account_manager;

    /** @var Git_SystemEventManager */
    private $system_event_manager;

    public function injectDependencies(
        UserManager $user_manager,
        Dumper $sshkey_dumper,
        Git_UserAccountManager $git_user_account_manager,
        Git_SystemEventManager $system_event_manager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->user_manager             = $user_manager;
        $this->sshkey_dumper            = $sshkey_dumper;
        $this->git_user_account_manager = $git_user_account_manager;
        $this->system_event_manager     = $system_event_manager;
        $this->logger                   = $logger;
    }

    private function getUserIdFromParameters()
    {
        $parameters = $this->getParametersAsArray();
        return intval($parameters[0]);
    }

    private function getOriginalSSHKeys()
    {
        $parameters = $this->getParametersAsArray();
        return $parameters[1];
    }

    private function getUserFromParameters()
    {
        $user = $this->user_manager->getUserById($this->getUserIdFromParameters());
        if ($user == null) {
            throw new UserNotExistException();
        }
        return $user;
    }

    public function process()
    {
        $user_id = $this->getUserIdFromParameters();
        $this->logger->debug('Dump key for user ' . $user_id);

        $user                   = $this->getUserFromParameters();
        $gitolite_admin_repo    = new GitRepositoryGitoliteAdmin();
        $invalid_keys_collector = new InvalidKeysCollector();

        $this->updateGitolite($user, $invalid_keys_collector);
        $are_keys_successfuly_deployed = ! $invalid_keys_collector->hasInvalidKeys();
        $this->system_event_manager->queueGrokMirrorManifest($gitolite_admin_repo);

        $warning_message = '';
        try {
            $this->updateGerrit($user);
            $this->done();
        } catch (Git_UserSynchronisationException $e) {
            $are_keys_successfuly_deployed = false;
            $warning_message               = 'Unable to propagate ssh keys on gerrit for user: ' . $user->getUnixName() . ': ' . $e->getMessage();
        }

        if ($are_keys_successfuly_deployed) {
            $this->done();
        } else {
            $this->warning(trim($warning_message . "\n" .  $invalid_keys_collector->textualizeKeysNotValid()));
        }
    }

    private function updateGitolite(PFUser $user, InvalidKeysCollector $invalid_keys_collector)
    {
        $this->logger->debug('Update ssh keys in Gitolite');
        $this->sshkey_dumper->dumpSSHKeys($user, $invalid_keys_collector);
    }

    private function updateGerrit(PFUser $user)
    {
        $this->logger->debug('Update ssh keys in Gerrit');
        $this->git_user_account_manager->synchroniseSSHKeys(
            $this->getKeysFromString($this->getOriginalSSHKeys()),
            $user->getAuthorizedKeysArray(),
            $user
        );
    }

    private function getKeysFromString($keys_as_string)
    {
        $user = new PFUser();
        $user->setAuthorizedKeys($keys_as_string);

        return array_filter($user->getAuthorizedKeysArray());
    }

    public function verbalizeParameters($with_link)
    {
        if ($with_link) {
            $user = $this->getUserFromParameters();
            if ($user) {
                $user_helper = UserHelper::instance();
                return $user_helper->getLinkOnUser($user);
            }
        }
        return $this->getUserIdFromParameters();
    }
}
