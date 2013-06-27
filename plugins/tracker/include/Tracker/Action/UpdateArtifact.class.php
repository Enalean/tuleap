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

class Tracker_Action_UpdateArtifact {

    /** @var Tracker_Artifact */
    private $artifact;

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    /** @var EventManager */
    private $event_manager;

    public function __construct(Tracker_Artifact $artifact, Tracker_FormElementFactory $form_element_factory, EventManager $event_manager) {
        $this->artifact             = $artifact;
        $this->form_element_factory = $form_element_factory;
        $this->event_manager        = $event_manager;
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $current_user) {
         //TODO : check permissions on this action?
        $comment_format = $this->artifact->validateCommentFormat($request, 'comment_formatnew');
        $this->artifact->setUseArtifactPermissions( $request->get('use_artifact_permissions') ? 1 : 0 );

        $fields_data = $request->get('artifact');

        $fields_data['request_method_called'] = 'artifact-update';
        $this->artifact->getTracker()->augmentDataFromRequest($fields_data);
        unset($fields_data['request_method_called']);

        try {

            $this->artifact->createNewChangeset($fields_data, $request->get('artifact_followup_comment'), $current_user, $request->get('email'), true, $comment_format);

            $art_link = $this->artifact->fetchDirectLinkToArtifact();
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_index', 'update_success', array($art_link)), CODENDI_PURIFIER_LIGHT);

            $redirect = $this->getRedirectUrlAfterArtifactUpdate($request);
            $this->artifact->summonArtifactRedirectors($request, $redirect);

            if ($request->isAjax()) {
                $this->sendAjaxCardsUpdateInfo($current_user, $this->artifact, $this->form_element_factory);
            } elseif ($request->existAndNonEmpty('from_overlay')) {
                echo '<script>window.parent.tuleap.cardwall.cardsEditInPlace.validateEdition('.$this->artifact->getId().')</script>';
                return;
            } else {
                $GLOBALS['Response']->redirect($redirect->toUrl());
            }
        } catch (Tracker_NoChangeException $e) {
            if ($request->isAjax()) {
                $this->sendAjaxCardsUpdateInfo($current_user, $this->artifact, $this->form_element_factory);
            } else {
                $GLOBALS['Response']->addFeedback('info', $e->getMessage(), CODENDI_PURIFIER_LIGHT);
                $render = new Tracker_Artifact_EditRenderer($this->event_manager, $this->artifact, $this->form_element_factory, $layout);
                $render->display($request, $current_user);
            }
        } catch (Tracker_Exception $e) {
            if ($request->isAjax()) {
                $this->sendAjaxCardsUpdateInfo($current_user, $this->artifact, $this->form_element_factory);
            } else {
                $GLOBALS['Response']->addFeedback('error', $e->getMessage());
                $render = new Tracker_Artifact_EditRenderer($this->event_manager, $this->artifact, $this->form_element_factory, $layout);
                $render->display($request, $current_user);
            }
        }
    }

    protected function getRedirectUrlAfterArtifactUpdate(Codendi_Request $request) {
        $stay     = $request->get('submit_and_stay') ;
        $from_aid = $request->get('from_aid');

        $redirect = new Tracker_Artifact_Redirect();
        $redirect->mode             = Tracker_Artifact_Redirect::STATE_SUBMIT;
        $redirect->base_url         = TRACKER_BASE_URL;
        $redirect->query_parameters = $this->calculateRedirectParams($stay, $from_aid);
        if ($stay) {
            $redirect->mode = Tracker_Artifact_Redirect::STATE_STAY_OR_CONTINUE;
        }
        return $redirect;
    }

    private function calculateRedirectParams($stay, $from_aid) {
        $redirect_params = array();
        if ($stay) {
            $redirect_params['aid']       = $this->artifact->getId();
            $redirect_params['from_aid']  = $from_aid;
        } else if ($from_aid) {
            $redirect_params['aid']       = $from_aid;
        } else {
            $redirect_params['tracker']   = $this->artifact->tracker_id;
        }
        return array_filter($redirect_params);
    }

    private function sendAjaxCardsUpdateInfo(PFUser $current_user) {
        $cards_info = $this->getCardUpdateInfo($this->artifact, $current_user);
        $parent     = $this->artifact->getParent($current_user);
        if ($parent) {
            $cards_info = $cards_info + $this->getCardUpdateInfo($parent, $current_user);
        }

        $GLOBALS['Response']->sendJSON($cards_info);
    }

    private function getCardUpdateInfo(Tracker_Artifact $artifact, PFUser $current_user) {
        $card_info               = array();
        $tracker_id              = $artifact->getTracker()->getId();
        $remaining_effort_field  = $this->form_element_factory->getComputableFieldByNameForUser(
            $tracker_id,
            Tracker::REMAINING_EFFORT_FIELD_NAME,
            $current_user
        );

        if ($remaining_effort_field) {
            $remaining_effort = $remaining_effort_field->fetchCardValue($artifact);
            $card_info[$artifact->getId()] = array(
                Tracker::REMAINING_EFFORT_FIELD_NAME => $remaining_effort
            );
        }
        return $card_info;
    }
}
?>
