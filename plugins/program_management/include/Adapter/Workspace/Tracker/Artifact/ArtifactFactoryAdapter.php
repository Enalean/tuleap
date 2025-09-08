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

namespace Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact;

use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Artifact\ArtifactIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Artifact\ArtifactNotFoundException;
use Tuleap\Tracker\Artifact\Artifact;

final class ArtifactFactoryAdapter implements RetrieveFullArtifact
{
    public function __construct(private \Tracker_ArtifactFactory $artifact_factory)
    {
    }

    #[\Override]
    public function getNonNullArtifact(ArtifactIdentifier $artifact_identifier): Artifact
    {
        $artifact = $this->artifact_factory->getArtifactById($artifact_identifier->getId());
        if (! $artifact) {
            throw new ArtifactNotFoundException($artifact_identifier);
        }
        return $artifact;
    }
}
