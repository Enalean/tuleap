<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;

/**
 * Execute the various command during a postSaveNewChangeset
 */
class Tracker_FormElement_Field_ArtifactLink_PostSaveNewChangesetQueue
{

    /** @var Tracker_FormElement_Field_ArtifactLink_PostSaveNewChangesetCommand[] */
    private $queue = [];

    public function add(Tracker_FormElement_Field_ArtifactLink_PostSaveNewChangesetCommand $command)
    {
        $this->queue[] = $command;
    }

    public function execute(
        Artifact $artifact,
        PFUser $submitter,
        Tracker_Artifact_Changeset $new_changeset,
        ?Tracker_Artifact_Changeset $previous_changeset = null
    ) {
        foreach ($this->queue as $command) {
            $command->execute($artifact, $submitter, $new_changeset, $previous_changeset);
        }
    }
}
