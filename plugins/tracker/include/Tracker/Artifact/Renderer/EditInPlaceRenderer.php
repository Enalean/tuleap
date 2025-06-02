<?php
/**
 * Copyright Enalean (c) 2014 - present. All rights reserved.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Renderer\FieldsDataFromRequestRetriever;
use Tuleap\Tracker\Artifact\PriorityManager;
use Tuleap\Tracker\Workflow\NoPossibleValueException;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;
use Tuleap\User\CurrentUserWithLoggedInInformation;

class Tracker_Artifact_Renderer_EditInPlaceRenderer // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function __construct(
        private Artifact $artifact,
        private MustacheRenderer $renderer,
        private HiddenFieldsetsDetector $hidden_fieldsets_detector,
        private FieldsDataFromRequestRetriever $fields_data_from_request_retriever,
    ) {
    }

    public function display(CurrentUserWithLoggedInInformation $current_user, Codendi_Request $request)
    {
        $submitted_values = $this->getSubmittedValues($request);

        $presenter = new Tracker_Artifact_Presenter_EditArtifactInPlacePresenter(
            $this->fetchFollowUps($current_user->user),
            $this->fetchArtifactLinks($current_user->user),
            $this->artifact->getTracker()->fetchFormElementsNoColumns($this->artifact, $submitted_values),
            $this->artifact,
            $current_user,
            $this->hidden_fieldsets_detector
        );
        $this->renderer->renderToPage('artifact-modal', $presenter);
    }

    public function getSubmittedValues($request): array
    {
        $submitted_values = $request->get('artifact');
        if (! $submitted_values || ! is_array($submitted_values)) {
            return [];
        }

        return $submitted_values;
    }

    /**
     * @return Tracker_Artifact_Presenter_ArtifactLinkPresenter[]
     */
    private function fetchArtifactLinks(PFUser $current_user)
    {
        $linked_artifacts = $this->artifact->getLinkedArtifacts($current_user);
        $links            = [];

        foreach ($linked_artifacts as $artifact) {
            $artifact_title = $artifact->getTitle();
            $artifact_link  = new Tracker_Artifact_Presenter_ArtifactLinkPresenter(
                $artifact->getTracker()->getItemName(),
                $artifact->getTracker()->getProject()->getID(),
                $artifact->getId(),
                ($artifact_title) ? $artifact_title : ''
            );

            $links[] = $artifact_link;
        }

        return $links;
    }

    private function fetchFollowUps(PFUser $current_user)
    {
        $changesets = $this->getFollowupsContent($this->artifact);
        $presenter  = new Tracker_Artifact_Presenter_FollowUpCommentsPresenter($changesets, $current_user);

        return $this->renderer->renderToString('follow-ups', $presenter);
    }

    private function getFollowupsContent(Artifact $artifact)
    {
        $followups_content = $artifact->getChangesets();
        array_shift($followups_content);

        $followups_content = array_merge($followups_content, $this->getPriorityHistory($artifact));

        usort($followups_content, [$this, 'compareFollowupsByDate']);

        return array_reverse($followups_content);
    }

    public function compareFollowupsByDate($first_followup, $second_followup)
    {
        return ($first_followup->getFollowUpDate() < $second_followup->getFollowUpDate()) ? -1 : 1;
    }

    private function getPriorityHistory(Artifact $artifact)
    {
        return $this->getPriorityManager()->getArtifactPriorityHistory($artifact);
    }

    private function getPriorityManager(): PriorityManager
    {
        return PriorityManager::build();
    }

    public function updateArtifact(Codendi_Request $request, PFUser $current_user)
    {
        $comment_format = $this->artifact->validateCommentFormat($request, 'comment_formatnew');

        try {
            $fields_data =  $this->fields_data_from_request_retriever->getAugmentedDataFromRequest($this->artifact, $request, $current_user);

            $this->artifact->createNewChangeset(
                $fields_data,
                $request->get('artifact_followup_comment'),
                $current_user,
                true,
                $comment_format
            );
        } catch (Tracker_NoChangeException $e) {
        } catch (NoPossibleValueException | Tracker_Exception $e) {
            $this->sendErrorsAsJson($e->getMessage());
        }
    }

    private function sendErrorsAsJson($exception_message)
    {
        $feedback            = [];
        $feedback['message'] = $exception_message;

        if ($GLOBALS['Response']->feedbackHasErrors()) {
            $feedback['errors'] = $GLOBALS['Response']->getFeedbackErrors();
        }

        $GLOBALS['Response']->send400JSONErrors($feedback);
    }
}
