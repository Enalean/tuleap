<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
require_once TRACKER_BASE_DIR.'/Tracker/Artifact/Redirect.class.php';

class Tracker_Action_CreateArtifact {
    private $tracker;
    private $artifact_factory;
    private $tracker_factory;
    private $formelement_factory;

    public function __construct(
        Tracker                    $tracker,
        Tracker_ArtifactFactory    $artifact_factory,
        TrackerFactory             $tracker_factory,
        Tracker_FormElementFactory $formelement_factory
    ) {
        $this->tracker             = $tracker;
        $this->artifact_factory    = $artifact_factory;
        $this->tracker_factory     = $tracker_factory;
        $this->formelement_factory = $formelement_factory;
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, User $current_user) {
        if ($this->tracker->userCanSubmitArtifact($current_user)) {
            $this->processCreate($layout, $request, $current_user);
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId());
        }
    }

    private function processCreate(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, User $current_user) {
        $link     = (int) $request->get('link-artifact-id');
        $artifact = $this->createArtifact($layout, $request, $current_user);
        if ($artifact) {
            $this->associateImmediatelyIfNeeded($artifact, $link, $request->get('immediate'), $current_user);
            $this->redirect($request, $current_user, $artifact);
        }
        $this->tracker->displaySubmit($layout, $request, $current_user, $link);
    }

    /**
     * Add an artefact in the tracker
     *
     * @param Tracker_IDisplayTrackerLayout  $layout
     * @param Codendi_Request                $request
     * @param User                           $user
     *
     * @return Tracker_Artifact the new artifact
     */
    private function createArtifact(Tracker_IDisplayTrackerLayout $layout, $request, $user) {
        $email = null;
        if ($user->isAnonymous()) {
            $email = $request->get('email');
        }

        $fields_data = $request->get('artifact');
        $this->tracker->augmentDataFromRequest($fields_data);

        return $this->artifact_factory->createArtifact($this->tracker, $fields_data, $user, $email);
    }

    private function associateImmediatelyIfNeeded(Tracker_Artifact $new_artifact, $link_artifact_id, $doitnow, User $current_user) {
        if ($link_artifact_id && $doitnow) {
            $source_artifact = $this->artifact_factory->getArtifactById($link_artifact_id);
            if ($source_artifact) {
                $source_artifact->linkArtifact($new_artifact->getId(), $current_user);
            }
        }
    }

    private function redirect(Codendi_Request $request, User $current_user, Tracker_Artifact $artifact) {
        $redirect = $this->getRedirect($request, $current_user, $artifact);
        $this->executeRedirect($request, $artifact, $redirect);
    }
    
    private function getRedirect(Codendi_Request $request, User $current_user, Tracker_Artifact $artifact) {
        $redirect = $this->redirectUrlAfterArtifactSubmission($request, $this->tracker->getId(), $artifact->getId());
        $this->redirectToParentCreationIfNeeded($artifact, $current_user, $redirect);
        $artifact->summonArtifactRedirectors($request, $redirect);
        return $redirect;
    }

    private function executeRedirect(Codendi_Request $request, Tracker_Artifact $artifact, Tracker_Artifact_Redirect $redirect) {
        if ($request->isAjax()) {
            header(json_header(array('aid' => $artifact->getId())));
            exit;
        } else if ($this->isFromOverlay($request)) {
            echo '<script>window.parent.codendi.tracker.artifact.artifactLink.newArtifact(' . (int) $artifact->getId() . ');</script>';
            exit;
        } else {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_index', 'create_success', array($artifact->fetchXRefLink())), CODENDI_PURIFIER_LIGHT);
            $GLOBALS['Response']->redirect($redirect->toUrl());
        }
    }

    private function isFromOverlay(Codendi_Request $request) {
        if ($request->existAndNonEmpty('link-artifact-id') && !$request->exist('immediate')) {
            return true;
        }
        return false;
    }

    protected function redirectToParentCreationIfNeeded(Tracker_Artifact $artifact, User $current_user, Tracker_Artifact_Redirect $redirect) {
        $parent_tracker = $this->tracker->getParent();
        if ($parent_tracker) {
            if (count($artifact->getAllAncestors($current_user)) == 0) {
                $art_link = $this->formelement_factory->getAnArtifactLinkField($current_user, $parent_tracker);
                if ($art_link) {
                    $art_link_key = 'artifact['.$art_link->getId().'][new_values]';
                    $redirect_params = array(
                        'tracker'     => $parent_tracker->getId(),
                        'func'        => 'new-artifact',
                        $art_link_key => $artifact->getId()
                    );
                    $redirect->mode             = Tracker_Artifact_Redirect::STATE_CREATE_PARENT;
                    $redirect->query_parameters = $redirect_params;
                }
            }
        }
    }

    protected function redirectUrlAfterArtifactSubmission(Codendi_Request $request, $tracker_id, $artifact_id) {
        $redirect = new Tracker_Artifact_Redirect();
        $redirect->base_url = TRACKER_BASE_URL;
        
        $stay      = $request->get('submit_and_stay');
        $continue  = $request->get('submit_and_continue');
        if ($stay || $continue) {
            $redirect->mode = Tracker_Artifact_Redirect::STATE_STAY_OR_CONTINUE;
        } else {
            $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;
        }
        $redirect->query_parameters = $this->calculateRedirectParams($tracker_id, $artifact_id, $stay, $continue);

        return $redirect;
    }

    private function calculateRedirectParams($tracker_id, $artifact_id, $stay, $continue) {
        $redirect_params = array();
        $redirect_params['tracker']       = $tracker_id;
        if ($continue) {
            $redirect_params['func']      = 'new-artifact';
        }
        if ($stay) {
            $redirect_params['aid']       = $artifact_id;
        }
        return array_filter($redirect_params);
    }

}

?>
