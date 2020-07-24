<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\GitLFS\Lock\Response;

use Tuleap\GitLFS\Lock\Lock;

class LockResponseBuilder
{
    public function buildSuccessfulLockCreation(Lock $lock): LockResponse
    {
        return new LockResponseSuccessfulCreationRepresentation(
            new LockResponseLockRepresentation($lock)
        );
    }

    public function buildSuccessfulLockList(array $locks): LockResponse
    {
        return new LockResponseSuccessfulListRepresentation(...$this->generateLocksRepresentations(...$locks));
    }

    public function buildSuccessfulLockVerify(array $ours, array $theirs): LockResponse
    {
        return new LockResponseSuccessfulVerifyRepresentation(
            $this->generateLocksRepresentations(...$ours),
            $this->generateLocksRepresentations(...$theirs)
        );
    }

    public function buildErrorResponse(string $message): LockResponse
    {
        return new LockResponseErrorRepresentation($message);
    }

    public function buildSuccessfulLockDestruction(Lock $lock): LockResponse
    {
        return new LockResponseSuccessfulDestructionRepresentation(
            new LockResponseLockRepresentation($lock)
        );
    }

    public function buildLockConflictErrorResponse(Lock $lock): LockResponse
    {
        return new LockResponseConflictErrorRepresentation(
            new LockResponseLockRepresentation($lock)
        );
    }

    private function generateLocksRepresentations(Lock ...$locks): array
    {
        $locks_representations = [];

        foreach ($locks as $lock) {
            $locks_representations[] = new LockResponseLockRepresentation($lock);
        }

        return $locks_representations;
    }
}
