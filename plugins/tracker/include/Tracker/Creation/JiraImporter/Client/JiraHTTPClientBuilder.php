<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Client;

use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Message\Authentication\BasicAuth;
use Http\Message\Authentication\Bearer;
use Psr\Http\Client\ClientInterface;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Tracker\Creation\JiraImporter\JiraCredentials;
use Tuleap\Tracker\Creation\JiraImporter\JiraInstanceURLChecker;

class JiraHTTPClientBuilder
{
    public static function buildHTTPClientFromCredentials(JiraCredentials $jira_credentials): ClientInterface
    {
        if (JiraInstanceURLChecker::isAJiraCloudURL($jira_credentials->getJiraUrl())) {
            $authentication = new BasicAuth($jira_credentials->getJiraUsername(), $jira_credentials->getJiraToken()->getString());
        } else {
            $authentication = new Bearer($jira_credentials->getJiraToken()->getString());
        }

        return HttpClientFactory::createClient(
            new AuthenticationPlugin(
                $authentication
            )
        );
    }
}
