<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\CheckProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;

final class CheckProgramIncrementStub implements CheckProgramIncrement
{
    private bool $is_allowed;

    private function __construct(bool $is_allowed)
    {
        $this->is_allowed = $is_allowed;
    }

    public function checkIsAProgramIncrement(int $program_increment_id, \PFUser $user): void
    {
        if (! $this->is_allowed) {
            throw new ProgramIncrementNotFoundException($program_increment_id);
        }
    }

    public static function buildProgramIncrementChecker(): self
    {
        return new self(true);
    }

    public static function buildOtherArtifactChecker(): self
    {
        return new self(false);
    }
}
