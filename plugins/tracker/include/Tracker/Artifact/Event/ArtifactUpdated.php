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
    private \Tracker_Artifact_Changeset $changeset;
    /**
     * @psalm-readonly
     */
    private \Tracker_Artifact_Changeset $old_changeset;

    public function __construct(
        Artifact $artifact,
        \PFUser $user,
        \Tracker_Artifact_Changeset $changeset,
        \Tracker_Artifact_Changeset $old_changeset,
    ) {
        $this->artifact      = $artifact;
        $this->user          = $user;
        $this->changeset     = $changeset;
        $this->old_changeset = $old_changeset;
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
    public function getChangeset(): \Tracker_Artifact_Changeset
    {
        return $this->changeset;
    }

    /**
     * @psalm-mutation-free
     */
    public function getOldChangeset(): \Tracker_Artifact_Changeset
    {
        return $this->old_changeset;
    }
}
