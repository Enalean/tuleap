<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub\Artifact\Changeset\PostCreation;

use Closure;
use Tracker_Artifact_Changeset;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationActionsQueuer;

final class PostCreationActionsQueuerStub implements PostCreationActionsQueuer
{
    private int $count = 0;

    private function __construct(private ?Closure $callback)
    {
    }

    public static function doNothing(): self
    {
        return new self(null);
    }

    public static function withParameterAssertionCallbackHelper(\Closure $callback): self
    {
        return new self($callback);
    }

    public function queuePostCreation(
        Tracker_Artifact_Changeset $changeset,
        bool $send_notifications,
        array $mentioned_users,
    ): void {
        $this->count++;
        if ($this->callback) {
            ($this->callback)($changeset, $send_notifications, $mentioned_users);
        }
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
