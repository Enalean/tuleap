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

namespace Tuleap\Tracker\Creation\JiraImporter;

use Tuleap\Cryptography\ConcealedString;

/**
 * @psalm-immutable
 */
final readonly class JiraCredentials
{
    private string $jira_url;

    public function __construct(
        string $jira_url,
        private string $jira_username,
        private ConcealedString $jira_token,
    ) {
        $this->jira_url = rtrim($jira_url, '/');
    }

    public function getJiraToken(): ConcealedString
    {
        return $this->jira_token;
    }

    public function getJiraUsername(): string
    {
        return $this->jira_username;
    }

    public function getJiraUrl(): string
    {
        return $this->jira_url;
    }
}
