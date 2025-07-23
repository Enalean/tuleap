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
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredTimeboxIdentifier;

final class CreateArtifactStub implements CreateArtifact
{
    private int $call_count = 0;

    /**
     * @param MirroredTimeboxIdentifier[]     $return_values
     * @param MirroredTimeboxFirstChangeset[] $arguments
     */
    private function __construct(
        private bool $should_throw,
        private array $return_values,
        private array $arguments = [],
    ) {
    }

    public static function withIds(int ...$mirrored_timebox_ids): self
    {
        $return_values = array_map(
            static fn(int $id) => MirroredTimeboxIdentifierStub::withId($id),
            $mirrored_timebox_ids
        );
        return new self(false, $return_values);
    }

    public static function withError(): self
    {
        return new self(true, []);
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

    #[\Override]
    public function create(MirroredTimeboxFirstChangeset $first_changeset): MirroredTimeboxIdentifier
    {
        $this->call_count++;
        $this->arguments[] = $first_changeset;
        if ($this->should_throw) {
            throw new ArtifactCreationException();
        }
        if (count($this->return_values) > 0) {
            return array_shift($this->return_values);
        }
        throw new \LogicException('No artifact id configured');
    }
}
