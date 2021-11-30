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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\User;

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;

class JiraUserInfoQuerier
{
    public function __construct(
        private JiraClient $wrapper,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws JiraConnectionException
     */
    public function retrieveUserFromJiraAPI(string $account_id): JiraCloudUser
    {
        $this->logger->debug("User with account id $account_id is unknown, querying /user?accountId=$account_id ...");
        $user_response = $this->wrapper->getUrl(
            $this->getUserUrl($account_id)
        );

        if ($user_response === null) {
            throw JiraConnectionException::canNotRetrieveUserInfoException($account_id);
        }

        $jira_user = new ActiveJiraCloudUser($user_response);

        $this->logger->debug('Information of user ' . $jira_user->getDisplayName() . ' have been retrieved.');

        return $jira_user;
    }

    private function getUserUrl(string $account_id): string
    {
        $params = ['accountId' => $account_id];
        return ClientWrapper::JIRA_CORE_BASE_URL . '/user?' . http_build_query($params);
    }
}
