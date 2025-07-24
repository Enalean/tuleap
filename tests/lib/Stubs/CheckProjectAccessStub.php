<?php
/**
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
 */

declare(strict_types=1);

namespace Tuleap\Test\Stubs;

use Tuleap\Project\CheckProjectAccess;
use Tuleap\Project\ProjectAccessSuspendedException;

final class CheckProjectAccessStub implements CheckProjectAccess
{
    private int $call_count = 0;

    private function __construct(
        private readonly bool $is_not_found,
        private readonly bool $is_suspended,
        private readonly bool $is_deleted,
        private readonly bool $is_user_restricted,
        private readonly bool $is_private,
    ) {
    }

    public static function withValidAccess(): self
    {
        return new self(false, false, false, false, false);
    }

    public static function withNotValidProject(): self
    {
        return new self(true, false, false, false, false);
    }

    public static function withSuspendedProject(): self
    {
        return new self(false, true, false, false, false);
    }

    public static function withDeletedProject(): self
    {
        return new self(false, false, true, false, false);
    }

    public static function withRestrictedUserWithoutAccess(): self
    {
        return new self(false, false, false, true, false);
    }

    public static function withPrivateProjectWithoutAccess(): self
    {
        return new self(false, false, false, false, true);
    }

    #[\Override]
    public function checkUserCanAccessProject(\PFUser $user, \Project $project): void
    {
        $this->call_count++;
        if ($this->is_not_found) {
            throw new \Project_AccessProjectNotFoundException();
        }
        if ($this->is_suspended) {
            throw new ProjectAccessSuspendedException();
        }
        if ($this->is_deleted) {
            throw new \Project_AccessDeletedException();
        }
        if ($this->is_user_restricted) {
            throw new \Project_AccessRestrictedException();
        }
        if ($this->is_private) {
            throw new \Project_AccessPrivateException();
        }
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }
}
