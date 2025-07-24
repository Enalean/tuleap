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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUserPreference;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class RetrieveUserPreferenceStub implements RetrieveUserPreference
{
    private function __construct(private string $preference_name, private string $preference_value)
    {
    }

    public static function withNameAndValue(string $preference_name, string $preference_value): self
    {
        return new self($preference_name, $preference_value);
    }

    #[\Override]
    public function retrieveUserPreference(UserIdentifier $user_identifier, string $preference_name): ?string
    {
        if ($preference_name !== $this->preference_name) {
            return null;
        }

        return $this->preference_value;
    }
}
