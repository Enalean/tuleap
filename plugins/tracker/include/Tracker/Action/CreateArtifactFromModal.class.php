<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Tracker_Action_CreateArtifactFromModal
{

    /** @var Codendi_Request */
    private $request;

    /** @var Tracker */
    private $tracker;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    public function __construct(Codendi_Request $request, Tracker $tracker, Tracker_ArtifactFactory $tracker_artifact_factory)
    {
        $this->request                  = $request;
        $this->tracker                  = $tracker;
        $this->tracker_artifact_factory = $tracker_artifact_factory;
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

    private function createArtifact(PFUser $current_user)
    {
        $email = null;
        if ($current_user->isAnonymous()) {
            $email = $this->request->get('email');
        }

        $fields_data = $this->request->get('artifact');
        $this->tracker->augmentDataFromRequest($fields_data);

        return $this->tracker_artifact_factory->createArtifact($this->tracker, $fields_data, $current_user, $email);
    }

    private function linkArtifact(PFUser $current_user, Tracker_Artifact $new_artifact)
    {
        $artifact_link_id = $this->request->get('artifact-link-id');
        $source_artifact  = $this->tracker_artifact_factory->getArtifactById($artifact_link_id);

        if (! $source_artifact) {
            return;
        }

        $source_artifact->linkArtifact($new_artifact->getId(), $current_user);
    }

    private function sendJSONErrors()
    {
        $feedback            = array();
        $feedback['message'] = $GLOBALS['Language']->getText('plugin_tracker_modal_artifact', 'submit_error');

        if ($GLOBALS['Response']->feedbackHasErrors()) {
            $feedback['errors'] = $GLOBALS['Response']->getFeedbackErrors();
        }

        $GLOBALS['Response']->send400JSONErrors($feedback);
    }
}
