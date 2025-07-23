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

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\AddArtifactLinkChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\AddArtifactLinkChangesetException;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\ArtifactLinkChangeset;

final class AddArtifactLinkChangesetStub implements AddArtifactLinkChangeset
{
    private int $call_count = 0;

    /**
     * @param ArtifactLinkChangeset[] $arguments
     */
    private function __construct(private bool $should_throw, private array $arguments = [])
    {
    }

    public static function withCount(): self
    {
        return new self(false);
    }

    public static function withError(): self
    {
        return new self(true);
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }

    /**
     * @return ArtifactLinkChangeset[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    #[\Override]
    public function addArtifactLinkChangeset(ArtifactLinkChangeset $changeset): void
    {
        $this->call_count++;
        $this->arguments[] = $changeset;
        if ($this->should_throw) {
            throw new AddArtifactLinkChangesetException(
                $changeset->mirrored_program_increment,
                new \Exception('Parent exception')
            );
        }
    }
}
