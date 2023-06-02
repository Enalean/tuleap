<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ActionButtons;

use Tracker;
use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\Artifact\Artifact;

class MoveArtifactActionAllowedByPluginRetriever implements Dispatchable
{
    public const NAME = 'moveArtifactActionAllowedByPluginRetriever';

    private Tracker $tracker;
    private string $error                       = "";
    private bool $has_external_semantic_defined = false;
    private bool $can_be_moved                  = true;

    public function __construct(private readonly Artifact $artifact, private readonly \PFUser $user)
    {
        $this->tracker = $artifact->getTracker();
    }

    public function getTracker(): Tracker
    {
        return $this->tracker;
    }

    public function setCanNotBeMoveDueToExternalPlugin(string $error): void
    {
        $this->can_be_moved = false;
        $this->error        = $error;
    }

    public function hasExternalSemanticDefined(): bool
    {
        return $this->has_external_semantic_defined === true;
    }

    public function doesAnExternalPluginForbiddenTheMove(): bool
    {
        return $this->can_be_moved === false;
    }

    public function getErrorMessage(): string
    {
        return $this->error;
    }

    public function getArtifact(): Artifact
    {
        return $this->artifact;
    }

    public function getUser(): \PFUser
    {
        return $this->user;
    }
}
