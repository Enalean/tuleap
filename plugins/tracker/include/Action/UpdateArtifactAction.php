<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use Codendi_HTMLPurifier;
use Codendi_Request;
use EventManager;
use PFUser;
use Tracker;
use Tracker_Artifact_ReadOnlyRenderer;
use Tracker_Artifact_Redirect;
use Tracker_Exception;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_Computed;
use Tracker_FormElementFactory;
use Tracker_IDisplayTrackerLayout;
use Tracker_NoChangeException;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\CreateNewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\Artifact\Renderer\ArtifactViewCollectionBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildLinkRetriever;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;

final readonly class UpdateArtifactAction
{
    public function __construct(
        private Artifact $artifact,
        private Tracker_FormElementFactory $form_element_factory,
        private EventManager $event_manager,
        private TypeIsChildLinkRetriever $artifact_retriever,
        private VisitRecorder $visit_recorder,
        private HiddenFieldsetsDetector $hidden_fieldsets_detector,
        private CreateNewChangeset $new_changeset_creator,
    ) {
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $current_user): void
    {
        // TODO: check permissions on this action?
        $comment_format = $this->artifact->validateCommentFormat($request, 'comment_formatnew');

        $fields_data = $request->get('artifact');
        if ($fields_data === false) {
            $fields_data = [];
        }
        $fields_data['request_method_called'] = 'artifact-update';
        $this->artifact->getTracker()->augmentDataFromRequest($fields_data);
        unset($fields_data['request_method_called']);

        try {
            if ($current_user->isAnonymous()) {
                $request_email = $request->get('email');
                $email         = ($request_email !== false) ? $request_email : null;
                $current_user->setEmail($email);
            }
            $this->new_changeset_creator->create(NewChangeset::fromFieldsDataArray(
                $this->artifact,
                $fields_data,
                (string) $request->get('artifact_followup_comment'),
                $comment_format,
                [],
                $current_user,
                (int) $_SERVER['REQUEST_TIME'],
                new CreatedFileURLMapping(),
            ), PostCreationContext::withNoConfig(true));

            $art_link = $this->artifact->fetchDirectLinkToArtifact();
            $GLOBALS['Response']->addFeedback('info', sprintf(dgettext('tuleap-tracker', 'Successfully Updated (%1$s)'), $art_link), CODENDI_PURIFIER_LIGHT);

            $redirect = $this->getRedirectUrlAfterArtifactUpdate($request);
            $this->artifact->summonArtifactRedirectors($request, $redirect);

            if ($request->isAjax()) {
                $this->sendAjaxCardsUpdateInfo($current_user);
            } elseif ($request->existAndNonEmpty('from_overlay')) {
                $purifier  = Codendi_HTMLPurifier::instance();
                $csp_nonce = $GLOBALS['Response']->getCSPNonce();
                echo sprintf('<script type="text/javascript" nonce="%s">window.parent.tuleap.cardwall.cardsEditInPlace.validateEdition(%d);</script>', $purifier->purify($csp_nonce), $this->artifact->getId());
                return;
            } else {
                $GLOBALS['Response']->redirect($redirect->toUrl());
            }
        } catch (Tracker_NoChangeException $e) {
            if ($request->isAjax()) {
                $this->sendAjaxCardsUpdateInfo($current_user);
            } else {
                $GLOBALS['Response']->addFeedback('info', $e->getMessage(), CODENDI_PURIFIER_LIGHT);
                $render = new Tracker_Artifact_ReadOnlyRenderer(
                    $this->event_manager,
                    $this->artifact,
                    $layout,
                    $this->artifact_retriever,
                    $this->visit_recorder,
                    $this->hidden_fieldsets_detector,
                    new ArtifactViewCollectionBuilder($this->event_manager, $this->artifact_retriever)
                );
                $render->display($request, $current_user);
            }
        } catch (Tracker_Exception $e) {
            if ($request->isAjax()) {
                $this->sendAjaxCardsUpdateInfo($current_user);
            } else {
                $GLOBALS['Response']->addFeedback('error', $e->getMessage());
                $render = new Tracker_Artifact_ReadOnlyRenderer(
                    $this->event_manager,
                    $this->artifact,
                    $layout,
                    $this->artifact_retriever,
                    $this->visit_recorder,
                    $this->hidden_fieldsets_detector,
                    new ArtifactViewCollectionBuilder($this->event_manager, $this->artifact_retriever)
                );
                $render->display($request, $current_user);
            }
        }
    }

    public function getRedirectUrlAfterArtifactUpdate(Codendi_Request $request): Tracker_Artifact_Redirect
    {
        $stay     = $request->get('submit_and_stay');
        $from_aid = $request->get('from_aid');

        $redirect                   = new Tracker_Artifact_Redirect();
        $redirect->mode             = Tracker_Artifact_Redirect::STATE_SUBMIT;
        $redirect->base_url         = TRACKER_BASE_URL;
        $redirect->query_parameters = $this->calculateRedirectParams($stay, $from_aid);
        if ($stay) {
            $redirect->mode = Tracker_Artifact_Redirect::STATE_STAY;
        }
        return $redirect;
    }

    private function calculateRedirectParams($stay, $from_aid): array
    {
        $redirect_params = [];
        if ($stay) {
            $redirect_params['aid']      = $this->artifact->getId();
            $redirect_params['from_aid'] = $from_aid;
        } elseif ($from_aid) {
            $redirect_params['aid'] = $from_aid;
        } else {
            $redirect_params['tracker'] = $this->artifact->tracker_id;
        }
        return array_filter($redirect_params);
    }

    private function sendAjaxCardsUpdateInfo(PFUser $current_user): void
    {
        $cards_info = $this->getCardUpdateInfo($this->artifact, $current_user);
        $parent     = $this->artifact->getParent($current_user);
        if ($parent) {
            $cards_info = $cards_info + $this->getCardUpdateInfo($parent, $current_user);
        }

        $GLOBALS['Response']->sendJSON($cards_info);
    }

    private function getCardUpdateInfo(Artifact $artifact, PFUser $current_user): array
    {
        $card_info              = [];
        $tracker_id             = $artifact->getTracker()->getId();
        $remaining_effort_field = $this->form_element_factory->getComputableFieldByNameForUser(
            $tracker_id,
            Tracker::REMAINING_EFFORT_FIELD_NAME,
            $current_user
        );
        if ($remaining_effort_field !== null) {
            $remaining_effort = $remaining_effort_field->fetchCardValue($artifact);
            $remaining_effort = $this->addAutocomputeLabelIfFieldIsAutcocomputed($artifact, $remaining_effort_field, $remaining_effort);

            $card_info[$artifact->getId()] = [
                Tracker::REMAINING_EFFORT_FIELD_NAME => $remaining_effort,
            ];
        }
        return $card_info;
    }

    private function addAutocomputeLabelIfFieldIsAutcocomputed(
        Artifact $artifact,
        Tracker_FormElement_Field $remaining_effort_field,
        $remaining_effort,
    ) {
        if (
            $artifact->getTracker()->hasFormElementWithNameAndType($remaining_effort_field->getName(), ['computed'])
            && $remaining_effort_field instanceof Tracker_FormElement_Field_Computed
            && $remaining_effort_field->isArtifactValueAutocomputed($artifact)
        ) {
            $remaining_effort .= ' (' . dgettext('tuleap-tracker', 'autocomputed') . ')';
        }

        return $remaining_effort;
    }
}
