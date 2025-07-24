<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub;

use Tuleap\Tracker\Artifact\Artifact;

final readonly class RetrieveViewableArtifactStub implements \Tuleap\Tracker\Artifact\RetrieveViewableArtifact
{
    /**
     * @param list<Artifact> $artifacts
     */
    private function __construct(private array $artifacts)
    {
    }

    /**
     * @no-named-arguments
     */
    public static function withArtifacts(Artifact $artifact, Artifact ...$other_artifacts): self
    {
        return new self([$artifact, ...$other_artifacts]);
    }

    public static function withNoArtifact(): self
    {
        return new self([]);
    }

    #[\Override]
    public function getArtifactByIdUserCanView(\PFUser $user, int $id): ?Artifact
    {
        foreach ($this->artifacts as $artifact) {
            if ($artifact->getId() === $id) {
                return $artifact;
            }
        }
        return null;
    }
}
