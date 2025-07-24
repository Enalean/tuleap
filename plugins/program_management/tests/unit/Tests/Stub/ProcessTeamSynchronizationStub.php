<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

use Tuleap\ProgramManagement\Domain\Events\TeamSynchronizationEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProcessTeamSynchronization;

final class ProcessTeamSynchronizationStub implements ProcessTeamSynchronization
{
    private int $calls_count;

    private function __construct()
    {
        $this->calls_count = 0;
    }

    public static function build(): self
    {
        return new self();
    }

    #[\Override]
    public function processTeamSynchronization(TeamSynchronizationEvent $event): void
    {
        $this->calls_count++;
    }

    public function getCallsCount(): int
    {
        return $this->calls_count;
    }
}
