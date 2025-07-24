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

use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\RetrieveFullArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Artifact\ArtifactIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Artifact\ArtifactNotFoundException;
use Tuleap\Tracker\Artifact\Artifact;

final class RetrieveFullArtifactStub implements RetrieveFullArtifact
{
    private function __construct(private bool $should_throw, private bool $always_returns, private array $artifacts)
    {
    }

    public static function withArtifact(Artifact $artifact): self
    {
        return new self(false, true, [$artifact]);
    }

    /**
     * @no-named-arguments
     */
    public static function withSuccessiveArtifacts(Artifact $first_artifact, Artifact ...$other_artifacts): self
    {
        return new self(false, false, [$first_artifact, ...$other_artifacts]);
    }

    public static function withError(): self
    {
        return new self(true, false, []);
    }

    #[\Override]
    public function getNonNullArtifact(ArtifactIdentifier $artifact_identifier): Artifact
    {
        if ($this->should_throw) {
            throw new ArtifactNotFoundException($artifact_identifier);
        }
        if ($this->always_returns) {
            return $this->artifacts[0];
        }
        if (count($this->artifacts) > 0) {
            return array_shift($this->artifacts);
        }
        throw new \LogicException('No artifact configured');
    }
}
