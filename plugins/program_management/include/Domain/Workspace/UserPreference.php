<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Workspace;

final class UserPreference
{
    private function __construct(private string $preference_name, private ?string $preference_value)
    {
    }

    public static function fromUserIdentifierAndPreferenceName(
        RetrieveUserPreference $retrieve_user_preference,
        UserIdentifier $user_identifier,
        string $preference_name,
    ): self {
        return new self(
            $preference_name,
            $retrieve_user_preference->retrieveUserPreference($user_identifier, $preference_name)
        );
    }

    public function getPreferenceName(): string
    {
        return $this->preference_name;
    }

    public function getPreferenceValue(): ?string
    {
        return $this->preference_value;
    }
}
