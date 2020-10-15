<?php
/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Event;

use Tuleap\Event\Dispatchable;

final class ArtifactCreated implements Dispatchable
{
    public const NAME = 'trackerArtifactCreated';
    /**
     * @var \Tuleap\Tracker\Artifact\Artifact
     * @psalm-readonly
     */
    private $artifact;
    /**
     * @var \Tracker_Artifact_Changeset
     * @psalm-readonly
     */
    private $changeset;
    /**
     * @var \PFUser
     * @psalm-readonly
     */
    private $user;

    public function __construct(\Tuleap\Tracker\Artifact\Artifact $artifact, \Tracker_Artifact_Changeset $changeset, \PFUser $user)
    {
        $this->artifact  = $artifact;
        $this->changeset = $changeset;
        $this->user      = $user;
    }

    /**
     * @psalm-mutation-free
     */
    public function getArtifact(): \Tuleap\Tracker\Artifact\Artifact
    {
        return $this->artifact;
    }

    /**
     * @psalm-mutation-free
     */
    public function getChangeset(): \Tracker_Artifact_Changeset
    {
        return $this->changeset;
    }

    /**
     * @psalm-mutation-free
     */
    public function getUser(): \PFUser
    {
        return $this->user;
    }
}
