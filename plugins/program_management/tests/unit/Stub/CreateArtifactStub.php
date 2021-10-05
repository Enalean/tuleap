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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ArtifactCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\CreateArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredTimeboxFirstChangeset;

final class CreateArtifactStub implements CreateArtifact
{
    /**
     * @var MirroredTimeboxFirstChangeset[] $arguments
     */
    private function __construct(private int $call_count, private bool $should_throw, private array $arguments = [])
    {
    }

    public static function withCount(): self
    {
        return new self(0, false);
    }

    public static function withError(): self
    {
        return new self(0, true);
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }

    /**
     * @return MirroredTimeboxFirstChangeset[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function create(MirroredTimeboxFirstChangeset $first_changeset): void
    {
        $this->call_count++;
        $this->arguments[] = $first_changeset;
        if ($this->should_throw) {
            throw new ArtifactCreationException();
        }
    }
}
