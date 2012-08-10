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

class Tracker_Action_CreateArtifact {
    const STATE_NULL                = 'null';
    const STATE_CREATE_FROM_OVERLAY = 'from_overlay';
    const STATE_WILL_CREATE_PARENT  = 'to_parent';

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
            $state    = self::STATE_NULL;
            if ($request->existAndNonEmpty('link-artifact-id') && ! $request->exist('immediate')) {
                $state = self::STATE_CREATE_FROM_OVERLAY;
            }
            $link     = (int) $request->get('link-artifact-id');
            $artifact = $this->createArtifact($layout, $request, $current_user);
            if ($artifact) {
                $this->associateImmediatelyIfNeeded($artifact, $link, $request->get('immediate'), $current_user);

                $redirection = $this->redirectToParentCreationIfNeeded($artifact, $current_user);
                if ($redirection['can_redirect']) {
                    $redirection = $this->redirectUrlAfterArtifactSubmission($request, $this->tracker->getId(), $artifact->getId());
                }

                $redirection = $artifact->summonArtifactRedirectors($request, $redirection);

                if ($request->isAjax()) {
                    header(json_header(array('aid' => $artifact->getId())));
                    exit;
                } else if ($state == self::STATE_CREATE_FROM_OVERLAY) {
                    echo '<script>window.parent.codendi.tracker.artifact.artifactLink.newArtifact(' . (int) $artifact->getId() . ');</script>';
                    exit;
                } else {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_index', 'create_success', array($artifact->fetchXRefLink())), CODENDI_PURIFIER_LIGHT);
                    $GLOBALS['Response']->redirect($this->getUrlFromRedirection($redirection));
                }
            }
            $this->tracker->displaySubmit($layout, $request, $current_user, $link);
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId());
        }
    }

    private function getUrlFromRedirection($redirection) {
        return $redirection['base_url'].'/?'.  http_build_query($redirection['query_parameters']);
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

    protected function redirectToParentCreationIfNeeded(Tracker_Artifact $artifact, User $current_user) {
        $parent_tracker_id = $this->tracker->getHierarchy()->getParent($this->tracker->getId());
        if ($parent_tracker_id) {
            $parent_tracker = $this->tracker_factory->getTrackerById($parent_tracker_id);
            if ($parent_tracker) {
                if (count($artifact->getAllAncestors($current_user)) == 0) {
                    $art_link = $this->formelement_factory->getAnArtifactLinkField($current_user, $parent_tracker);
                    if ($art_link) {
                        $art_link_key = 'artifact['.$art_link->getId().'][new_values]';
                        $redirect_params = array(
                            'tracker'     => $parent_tracker_id,
                            'func'        => 'new-artifact',
                            $art_link_key => $artifact->getId()
                        );
                        $redirection['can_redirect'] = false;
                        $redirection['base_url'] = TRACKER_BASE_URL;
                        $redirection['query_parameters'] = $redirect_params;
                        return $redirection;
                    }
                }
            }
        }
        $redirection['can_redirect'] = true;
        $redirection['base_url'] = '';
        return $redirection;
    }

    protected function redirectUrlAfterArtifactSubmission(Codendi_Request $request, $tracker_id, $artifact_id) {
        $redirection['base_url'] = TRACKER_BASE_URL;
        $stay      = $request->get('submit_and_stay');
        $continue  = $request->get('submit_and_continue');
        if ($stay || $continue) {
            $redirection['can_redirect'] = false;
        } else {
            $redirection['can_redirect'] = true;
        }
        $redirection['query_parameters'] = $this->calculateRedirectParams($tracker_id, $artifact_id, $stay, $continue);

        return $redirection;
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
