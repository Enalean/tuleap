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

/**
 * Update the link direction in order to ensure that it is correct resp. the
 * association definition.
 */
class Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand implements
    Tracker_FormElement_Field_ArtifactLink_PostSaveNewChangesetCommand
{

    /** @var Tracker_FormElement_Field_ArtifactLink */
    private $field;

    public function __construct(Tracker_FormElement_Field_ArtifactLink $field) {
        $this->field = $field;
    }

    /**
     * @see Tracker_FormElement_Field_ArtifactLink_PostSaveNewChangesetCommand::execute()
     */
    public function execute(
        Tracker_Artifact $artifact,
        PFUser $submitter,
        Tracker_Artifact_Changeset $new_changeset,
        Tracker_Artifact_Changeset $previous_changeset = null
    ) {
        $new_value      = $new_changeset->getValue($this->field);
        $previous_value = $previous_changeset->getValue($this->field);

        $diff = $new_value->getArtifactLinkInfoDiff($previous_value);
        if ($diff->hasChanges()) {
            $GLOBALS['Response']->addFeedback('info', $artifact->getId().' added: '.$diff->getAddedFormatted('text'));
            $GLOBALS['Response']->addFeedback('info', $artifact->getId().' removed: '.$diff->getRemovedFormatted('text'));
        }
    }
}
