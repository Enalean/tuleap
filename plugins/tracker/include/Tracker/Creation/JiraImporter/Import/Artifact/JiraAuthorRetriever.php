<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact;

use PFUser;
use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\JiraUser;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;
use UserManager;

class JiraAuthorRetriever
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var JiraUserOnTuleapCache
     */
    private $user_cache;

    /**
     * @var JiraUserInfoQuerier
     */
    private $jira_user_querier;

    /**
     * @var PFUser
     */
    private $default_user;

    public function __construct(
        LoggerInterface $logger,
        UserManager $user_manager,
        JiraUserOnTuleapCache $user_cache,
        JiraUserInfoQuerier $jira_user_querier,
        PFUser $default_user
    ) {
        $this->logger            = $logger;
        $this->user_manager      = $user_manager;
        $this->user_cache        = $user_cache;
        $this->jira_user_querier = $jira_user_querier;
        $this->default_user      = $default_user;
    }

    public function retrieveArtifactSubmitter(IssueAPIRepresentation $issue): PFUser
    {
        $creator = $issue->getFieldByKey('creator');

        if ($creator === null) {
            return $this->default_user;
        }

        return $this->retrieveUser(
            new JiraUser($creator)
        );
    }

    private function retrieveUser(JiraUser $jira_user): PFUser
    {
        $display_name = $jira_user->getDisplayName();

        if ($this->user_cache->isUserCached($jira_user)) {
            $this->logger->debug("User $display_name is already in cache, skipping...");

            return $this->user_cache->getUserFromCache(
                $jira_user
            );
        }

        if ($jira_user->getEmailAddress() === '') {
            $this->logger->debug("Jira user $display_name does not share his/her email address, skipping...");
            $this->user_cache->cacheUser($this->default_user, $jira_user);

            return $this->default_user;
        }

        $matching_users = $this->user_manager->getAllUsersByEmail($jira_user->getEmailAddress());

        if (count($matching_users) !== 1) {
            $this->logger->debug("Unable to identify an unique user on Tuleap side for Jira user $display_name");

            $this->user_cache->cacheUser($this->default_user, $jira_user);
            return $this->default_user;
        }

        $tuleap_user           = $matching_users[0];
        $tuleap_user_real_name = $tuleap_user->getRealName();

        $this->user_cache->cacheUser($tuleap_user, $jira_user);
        $this->logger->debug("Jira user $display_name has been identified as Tuleap user $tuleap_user_real_name");

        return $tuleap_user;
    }

    public function retrieveJiraAuthor(JiraUser $update_author): PFUser
    {
        return $this->retrieveUser($update_author);
    }

    /**
     * @throws JiraConnectionException
     */
    public function getAssignedTuleapUser(string $jira_account_id): PFUser
    {
        if ($this->user_cache->hasUserWithAccountId($jira_account_id)) {
            return $this->user_cache->getUserFromCacheByJiraAccountId($jira_account_id);
        }

        $jira_user = $this->jira_user_querier->retrieveUserFromJiraAPI($jira_account_id);

        return $this->retrieveUser($jira_user);
    }
}
