<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Workspace\ProjectIdentifier;

/**
 * @psalm-immutable
 */
final class ProjectIdentifierStub implements ProjectIdentifier
{
    private int $project_id;

    private function __construct(int $project_id)
    {
        $this->project_id = $project_id;
    }

    public static function build(): self
    {
        return new self(101);
    }

    public static function buildWithId(int $id): self
    {
        return new self($id);
    }

    public function getId(): int
    {
        return $this->project_id;
    }
}
