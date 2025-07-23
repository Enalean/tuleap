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

namespace Tuleap\MediawikiStandalone\Instance;

final class OngoingInitializationsStateStub implements OngoingInitializationsState
{
    private bool $is_started  = false;
    private bool $is_finished = false;
    private bool $is_error    = false;

    private function __construct()
    {
    }

    public static function buildSelf(): self
    {
        return new self();
    }

    #[\Override]
    public function startInitialization(\Project $project): void
    {
        $this->is_started = true;
    }

    public function isStarted(): bool
    {
        return $this->is_started;
    }

    #[\Override]
    public function markAsError(\Project $project): void
    {
        $this->is_error = true;
    }

    public function isError(): bool
    {
        return $this->is_error;
    }

    #[\Override]
    public function finishInitialization(\Project $project): void
    {
        $this->is_finished = true;
    }

    public function isFinished(): bool
    {
        return $this->is_finished;
    }
}
