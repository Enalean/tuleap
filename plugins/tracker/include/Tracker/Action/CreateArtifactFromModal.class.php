<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValuesContainer;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;

class Tracker_Action_CreateArtifactFromModal
{
    public function __construct(
        private readonly Codendi_Request $request,
        private readonly Tracker $tracker,
        private readonly TrackerArtifactCreator $artifact_creator,
        private readonly Tracker_ArtifactFactory $tracker_artifact_factory,
    ) {
    }

    public function process(PFUser $current_user)
    {
        $new_artifact = $this->createArtifact($current_user);

        if ($new_artifact) {
            $this->linkArtifact($current_user, $new_artifact);
        } else {
            $this->sendJSONErrors();
        }
    }

    private function createArtifact(PFUser $current_user): ?Artifact
    {
        $fields_data = $this->request->get('artifact');
        $this->tracker->augmentDataFromRequest($fields_data);

        return $this->artifact_creator->create(
            $this->tracker,
            new InitialChangesetValuesContainer($fields_data, Option::nothing(NewArtifactLinkInitialChangesetValue::class)),
            $current_user,
            \Tuleap\Request\RequestTime::getTimestamp(),
            true,
            true,
            new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext(),
        );
    }

    private function linkArtifact(PFUser $current_user, Artifact $new_artifact)
    {
        $artifact_link_id = $this->request->get('artifact-link-id');
        $source_artifact  = $this->tracker_artifact_factory->getArtifactById($artifact_link_id);

        if (! $source_artifact) {
            return;
        }

        $source_artifact->linkArtifact(
            $new_artifact->getId(),
            $current_user,
            Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD
        );
    }

    private function sendJSONErrors()
    {
        $feedback            = [];
        $feedback['message'] = dgettext('tuleap-tracker', 'The artifact cannot be created because there are several errors:');

        if ($GLOBALS['Response']->feedbackHasErrors()) {
            $feedback['errors'] = $GLOBALS['Response']->getFeedbackErrors();
        }

        $GLOBALS['Response']->send400JSONErrors($feedback);
    }
}
