<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\User;

/**
 * @psalm-immutable
 */
final class ActiveJiraServerUser implements JiraUser
{
    private function __construct(private string $username, private string $display_name, private string $email_address)
    {
    }

    /**
     * @param array{name: string, displayName: string, emailAddress?: string} $user_json
     */
    public static function buildFromPayload(array $user_json): self
    {
        return new self($user_json['name'], $user_json['displayName'], $user_json['emailAddress'] ?? JiraUser::NO_EMAIL_ADDRESS_SHARED);
    }

    #[\Override]
    public function getDisplayName(): string
    {
        return $this->display_name;
    }

    #[\Override]
    public function getEmailAddress(): string
    {
        return $this->email_address;
    }

    #[\Override]
    public function getUniqueIdentifier(): string
    {
        return $this->username;
    }
}
