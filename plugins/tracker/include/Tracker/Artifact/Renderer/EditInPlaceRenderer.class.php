<?php
/**
 * Copyright Enalean (c) 2014. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class Tracker_Artifact_Renderer_EditInPlaceRenderer{

    /** @var Tracker_Artifact */
    private $artifact;

    /** @var MustacheRenderer */
    private $renderer;

    public function __construct(Tracker_Artifact $artifact, MustacheRenderer $renderer) {
        $this->renderer = $renderer;
        $this->artifact = $artifact;
    }

    public function display(PFUser $current_user) {
        $redirect         = new Tracker_Artifact_Redirect();
        $redirect->query_parameters['func'] = 'update-in-place';

        $presenter = new Tracker_Artifact_Presenter_EditArtifactInPlacePresenter(
            $this->fetchFollowUps(),
            $this->fetchArtifactLinks($current_user),
            $redirect->toUrl(),
            $this->artifact->getTracker()->fetchFormElementsNoColumns($this->artifact, array(0 => null)),
            $this->artifact
        );
        $this->renderer->renderToPage('artifact-modal', $presenter);
    }

    /**
     * @param PFUser $current_user
     * @return Tracker_Artifact_Presenter_ArtifactLinkPresenter[]
     */
    private function fetchArtifactLinks(PFUser $current_user) {
        $linked_artifacts = $this->artifact->getLinkedArtifacts($current_user);
        $links = array();

        foreach ($linked_artifacts as $artifact) {
            $artifact_link = new Tracker_Artifact_Presenter_ArtifactLinkPresenter(
                $artifact->getTracker()->getItemName(),
                $artifact->getTracker()->getProject()->getID(),
                $artifact->getId(),
                $artifact->getTitle()
            );

            $links[] = $artifact_link;
        }

        return $links;
    }

    private function fetchFollowUps() {
        $changesets = $this->getNonInitialChangesets($this->artifact);
        $presenter  = new Tracker_Artifact_Presenter_FollowUpCommentsPresenter($changesets);

        return $this->renderer->renderToString('follow-ups', $presenter);
    }

    /**
     * @param Tracker_Artifact $artifact
     * @return Tracker_Artifact_Changeset[]
     */
    private function getNonInitialChangesets(Tracker_Artifact $artifact) {
        $changesets = $artifact->getChangesets();
        array_shift($changesets);

        return $changesets;
    }

    public function updateArtifact(Codendi_Request $request, PFUser $current_user) {
        $comment_format = $this->artifact->validateCommentFormat($request, 'comment_formatnew');
        $fields_data    =  $this->getAugmentedDataFromRequest($request);

        try {
            $this->artifact->createNewChangeset(
                $fields_data,
                $request->get('artifact_followup_comment'),
                $current_user,
                true,
                $comment_format
            );
        } catch (Tracker_NoChangeException $e) {
        } catch (Tracker_Exception $e) {
            $this->sendErrorsAsJson($e->getMessage());
        }
    }

    private function getAugmentedDataFromRequest(Codendi_Request $request) {
        //this handles the 100 value on multi-select boxes
        $fields_data = $request->get('artifact');
        $fields_data['request_method_called'] = 'artifact-update';
        $this->artifact->getTracker()->augmentDataFromRequest($fields_data);
        unset($fields_data['request_method_called']);

        return $fields_data;
    }

    private function sendErrorsAsJson($exception_message) {
        $feedback = array($exception_message);
        if ($GLOBALS['Response']->feedbackHasErrors()) {
            $feedback = array_merge($feedback, $GLOBALS['Response']->getFeedbackErrors());
        }

        $GLOBALS['Response']->send400JSONErrors($feedback);
    }
}
?>
