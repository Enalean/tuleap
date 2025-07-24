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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\BuildProgramIncrementInfo;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementInfo;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class BuildProgramIncrementInfoStub implements BuildProgramIncrementInfo
{
    private function __construct(private ProgramIncrementInfo $increment_info)
    {
    }

    public static function withId(int $id): self
    {
        return new self(
            ProgramIncrementInfo::fromIncrementInfo(
                $id,
                "Program increment #$id",
                'Oct 01',
                'Oct 31'
            )
        );
    }

    #[\Override]
    public function build(UserIdentifier $user_identifier, ProgramIncrementIdentifier $increment_identifier): ProgramIncrementInfo
    {
        return $this->increment_info;
    }
}
