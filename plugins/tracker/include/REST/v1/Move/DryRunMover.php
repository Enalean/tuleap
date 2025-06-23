<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Move;

use PFUser;
use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Action\CollectDryRunTypingField;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Exception\MoveArtifactSemanticsException;
use Tuleap\Tracker\REST\v1\ArtifactPatchResponseRepresentation;
use Tuleap\Tracker\Tracker;

final class DryRunMover implements MoveDryRun
{
    public function __construct(private readonly CollectDryRunTypingField $collect_dry_run_typing_field)
    {
    }

    /**
     * @throws MoveArtifactSemanticsException
     */
    public function move(
        Tracker $source_tracker,
        Tracker $destination_tracker,
        Artifact $artifact,
        PFUser $user,
        LoggerInterface $logger,
    ): ArtifactPatchResponseRepresentation {
        return ArtifactPatchResponseRepresentation::fromDuckTypedCollection(
            $this->collect_dry_run_typing_field->collect($source_tracker, $destination_tracker, $artifact, $user, $logger)
        );
    }
}
