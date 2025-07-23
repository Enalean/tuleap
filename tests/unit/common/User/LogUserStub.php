<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\User;

use PFUser;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Test\Builders\UserTestBuilder;

final class LogUserStub implements LogUser
{
    private bool $has_been_logged_in = false;

    private function __construct()
    {
    }

    public static function buildSelf(): self
    {
        return new self();
    }

    #[\Override]
    public function login(string $name, ConcealedString $pwd): PFUser
    {
        $this->has_been_logged_in = true;

        return UserTestBuilder::anActiveUser()
            ->withUserName($name)
            ->build();
    }

    public function hasBeenLoggedIn(): bool
    {
        return $this->has_been_logged_in;
    }
}
