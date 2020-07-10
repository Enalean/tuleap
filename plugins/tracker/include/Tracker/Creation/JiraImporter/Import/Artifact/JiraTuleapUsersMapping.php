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

use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\JiraUser;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;

class JiraTuleapUsersMapping
{
    /**
     * @var array
     */
    private $identified_users = [];

    /**
     * @var array
     */
    private $user_emails_not_matching = [];

    /**
     * @var array
     */
    private $unknown_users = [];

    public function addUserMapping(JiraUser $jira_user, \PFUser $tuleap_user): void
    {
        $has_been_identified           = (int) $tuleap_user->getId() !== (int) TrackerImporterUser::ID;
        $has_email_address_been_shared = $jira_user->getEmailAddress() !== JiraUser::NO_EMAIL_ADDRESS_SHARED;

        if ($has_been_identified) {
            $this->identified_users[] = [
                'jira_display_name'       => $jira_user->getDisplayName(),
                'tuleap_user_real_name'   => $tuleap_user->getRealName(),
                'tuleap_user_profile_url' => $this->getBaseUrl() . $tuleap_user->getPublicProfileUrl(),
                'tuleap_user_username'    => $tuleap_user->getUserName()
            ];

            return;
        }

        if ($has_email_address_been_shared) {
            $this->user_emails_not_matching[] = [
                'jira_display_name' => $jira_user->getDisplayName(),
            ];

            return;
        }

        $this->unknown_users[] = [
            'jira_display_name' => $jira_user->getDisplayName(),
        ];
    }

    public function getIdentifiedUsers(): array
    {
        return $this->identified_users;
    }

    public function getUserEmailsNotMatching(): array
    {
        return $this->user_emails_not_matching;
    }

    public function getUnknownUsers(): array
    {
        return $this->unknown_users;
    }

    private function getBaseUrl(): string
    {
        return 'https://' . \ForgeConfig::get('sys_https_host');
    }
}
