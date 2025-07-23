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

use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\BuildProgramFlags;
use Tuleap\ProgramManagement\Domain\Workspace\ProgramFlag;

final class BuildProgramFlagsStub implements BuildProgramFlags
{
    /**
     * @param ProgramFlag[] $program_flags
     */
    public function __construct(private array $program_flags)
    {
    }

    public static function withDefaults(): self
    {
        return new self([
            ProgramFlag::fromLabelAndDescription('Top Secret', 'For authorized eyes only'),
        ]);
    }

    /**
     * @return ProgramFlag[]
     */
    #[\Override]
    public function build(ProgramIdentifier $program_identifier): array
    {
        return $this->program_flags;
    }
}
