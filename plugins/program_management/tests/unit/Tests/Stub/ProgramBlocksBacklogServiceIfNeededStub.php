<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

use Tuleap\ProgramManagement\Domain\Workspace\ProjectIdentifier;

final class ProgramBlocksBacklogServiceIfNeededStub implements \Tuleap\ProgramManagement\Domain\Workspace\ProgramBlocksBacklogServiceIfNeeded
{
    private function __construct(private readonly bool $should_block)
    {
    }

    public static function withBlocked(): self
    {
        return new self(true);
    }

    public static function withNotBlocked(): self
    {
        return new self(false);
    }

    public function shouldBacklogServiceBeBlocked(ProjectIdentifier $project_identifier): bool
    {
        return $this->should_block;
    }
}
