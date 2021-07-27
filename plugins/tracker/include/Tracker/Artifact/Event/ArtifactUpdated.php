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

namespace Tuleap\Tracker\Artifact\Event;

use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\Artifact\Artifact;

final class ArtifactUpdated implements Dispatchable
{
    public const NAME = 'trackerArtifactUpdated';

    /**
     * @psalm-readonly
     */
    private Artifact $artifact;
    /**
     * @psalm-readonly
     */
    private \PFUser $user;
    /**
     * @psalm-readonly
     */
    private \Project $project;

    public function __construct(Artifact $artifact, \PFUser $user, \Project $project)
    {
        $this->artifact = $artifact;
        $this->user     = $user;
        $this->project  = $project;
    }

    /**
     * @psalm-mutation-free
     */
    public function getArtifact(): Artifact
    {
        return $this->artifact;
    }

    /**
     * @psalm-mutation-free
     */
    public function getUser(): \PFUser
    {
        return $this->user;
    }

    /**
     * @psalm-mutation-free
     */
    public function getProject(): \Project
    {
        return $this->project;
    }
}
