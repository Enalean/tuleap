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

/**
 * @psalm-immutable
 */
final class ActiveJiraCloudUser implements JiraCloudUser
{
    private string $display_name;
    private string $jira_account_id;
    private string $email_address;

    public function __construct(array $update_author)
    {
        $this->display_name    = $update_author['displayName'];
        $this->jira_account_id = $update_author['accountId'];
        $this->email_address   = $update_author['emailAddress'] ?? self::NO_EMAIL_ADDRESS_SHARED;
    }

    #[\Override]
    public function getDisplayName(): string
    {
        return $this->display_name;
    }

    #[\Override]
    public function getJiraAccountId(): string
    {
        return $this->jira_account_id;
    }

    #[\Override]
    public function getEmailAddress(): string
    {
        return $this->email_address;
    }

    #[\Override]
    public function getUniqueIdentifier(): string
    {
        return $this->getJiraAccountId();
    }
}
