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

use Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationCollection;

/**
 * Update the link direction in order to ensure that it is correct resp. the
 * association definition.
 */
class Tracker_FormElement_Field_ArtifactLink_UpdateLinkingDirectionCommand implements
    Tracker_FormElement_Field_ArtifactLink_PostSaveNewChangesetCommand
{

    /** @var SourceOfAssociationCollection */
    private $source_of_association;

    public function __construct(SourceOfAssociationCollection $source_of_association)
    {
        $this->source_of_association = $source_of_association;
    }

    /**
     * @see Tracker_FormElement_Field_ArtifactLink_PostSaveNewChangesetCommand::execute()
     */
    public function execute(
        Tracker_Artifact $artifact,
        PFUser $submitter,
        Tracker_Artifact_Changeset $new_changeset,
        ?Tracker_Artifact_Changeset $previous_changeset = null
    ) {
        $this->source_of_association->linkToArtifact($artifact, $submitter);
    }
}
