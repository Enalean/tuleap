<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Tests\Stub;

use PFUser;
use Tuleap\Timetracking\Widget\People\VerifyManagerCanSeeTimetrackingOfUser;

final class VerifyManagerCanSeeTimetrackingOfUserStub implements VerifyManagerCanSeeTimetrackingOfUser
{
    private bool $called = false;

    private function __construct(private readonly bool $allowed)
    {
    }

    public static function allowed(): self
    {
        return new self(true);
    }

    public static function notAllowed(): self
    {
        return new self(false);
    }

    #[\Override]
    public function isManagerAllowedToSeeTimetrackingOfUser(PFUser $manager, PFUser $user): bool
    {
        $this->called = true;

        return $this->allowed;
    }

    public function isCalled(): bool
    {
        return $this->called;
    }
}
