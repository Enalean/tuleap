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

use Closure;
use PFUser;
use Tuleap\Timetracking\Permissions\VerifyUserCanSeeAllTimesInTracker;
use Tuleap\Tracker\Tracker;

final class VerifyUserCanSeeAllTimesInTrackerStub implements VerifyUserCanSeeAllTimesInTracker
{
    private int $nb_called = 0;

    /**
     * @param Closure(PFUser $user): bool $callback
     */
    private function __construct(private readonly Closure $callback)
    {
    }

    public static function withAllowed(): self
    {
        return new self(static fn () => true);
    }

    public static function withoutAllowed(): self
    {
        return new self(static fn () => false);
    }

    public static function withAllowedUser(PFUser $allowed): self
    {
        return new self(static fn (PFUser $user) => $user === $allowed);
    }

    #[\Override]
    public function userCanSeeAllTimesInTracker(PFUser $user, Tracker $tracker): bool
    {
        $this->nb_called++;

        return call_user_func($this->callback, $user);
    }

    public function getNbCalled(): int
    {
        return $this->nb_called;
    }
}
