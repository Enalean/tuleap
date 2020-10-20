<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Heartbeat;

use Project;
use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\Artifact\Artifact;

class OverrideArtifactsInFavourOfAnOther implements Dispatchable
{
    public const NAME = 'overrideArtifactsInFavourOfAnOther';

    /**
     * @var Artifact[]
     */
    private $overridden_artifacts;
    /**
     * @var Artifact[]
     * @psalm-readonly
     */
    private $artifacts;
    /**
     * @var \PFUser
     * @psalm-readonly
     */
    private $user;
    /**
     * @var Project
     * @psalm-readonly
     */
    private $project;

    /**
     * @param Artifact[] $artifacts
     */
    public function __construct(array $artifacts, \PFUser $user, Project $project)
    {
        $this->artifacts = $artifacts;
        $this->user      = $user;
        $this->project   = $project;
    }

    public function overrideArtifacts(array $artifacts): void
    {
        $this->overridden_artifacts = $artifacts;
    }

    public function getOverriddenArtifacts(): array
    {
        if ($this->overridden_artifacts !== null) {
            return $this->overridden_artifacts;
        }

        return $this->artifacts;
    }

    public function getArtifacts(): array
    {
        return $this->artifacts;
    }

    public function getUser(): \PFUser
    {
        return $this->user;
    }

    public function getProject(): Project
    {
        return $this->project;
    }
}
