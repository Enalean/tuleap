<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\JSONHeader;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValuesContainer;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;

class Tracker_Action_CreateArtifact
{
    public function __construct(
        private readonly Tracker $tracker,
        private readonly TrackerArtifactCreator $artifact_creator,
        private readonly Tracker_ArtifactFactory $artifact_factory,
        private readonly Tracker_FormElementFactory $formelement_factory,
    ) {
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $current_user)
    {
        if ($this->tracker->userCanSubmitArtifact($current_user)) {
            $this->processCreate($layout, $request, $current_user);
        } else {
            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker', 'Access denied. You don\'t have permissions to perform this action.'));
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId());
        }
    }

    private function processCreate(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $current_user)
    {
        $link     = (int) $request->get('link-artifact-id');
        $artifact = $this->createArtifact($layout, $request, $current_user);
        if ($artifact) {
            $this->associateImmediatelyIfNeeded($artifact, $request, $current_user);
            $this->redirect($request, $current_user, $artifact);
        }
        assert($layout instanceof Tracker_IFetchTrackerSwitcher);
        $this->tracker->displaySubmit($layout, $request, $current_user, $link);
    }

    /**
     * Add an artefact in the tracker
     *
     * @param Codendi_Request                $request
     * @param PFUser                         $user
     */
    private function createArtifact(Tracker_IDisplayTrackerLayout $layout, $request, $user): ?Artifact
    {
        $fields_data = $request->get('artifact');
        if (! isset($fields_data['request_method_called'])) {
            $fields_data['request_method_called'] = $request->get('func');
        }

        $this->tracker->augmentDataFromRequest($fields_data);

        return $this->artifact_creator->create(
            $this->tracker,
            new InitialChangesetValuesContainer($fields_data, Option::nothing(NewArtifactLinkInitialChangesetValue::class)),
            $user,
            \Tuleap\Request\RequestTime::getTimestamp(),
            true,
            true,
            new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext(),
            false,
        );
    }

    protected function associateImmediatelyIfNeeded(Artifact $new_artifact, \Codendi_Request $request, PFUser $current_user): void
    {
        $link_artifact_id   = (int) $request->get('link-artifact-id');
        $is_immediate       = (bool) $request->get('immediate');
        $artifact_link_type = $request->get('link-type') ?: "";

        if (! $link_artifact_id || ! $is_immediate) {
            return;
        }

        $source_artifact = $this->artifact_factory->getArtifactById($link_artifact_id);
        if (! $source_artifact) {
            return;
        }

        $source_artifact->linkArtifact(
            $new_artifact->getId(),
            $current_user,
            $artifact_link_type ?: Tracker_FormElement_Field_ArtifactLink::NO_TYPE
        );
    }

    private function redirect(Codendi_Request $request, PFUser $current_user, Artifact $artifact)
    {
        $redirect = $this->getRedirect($request, $current_user, $artifact);
        $this->executeRedirect($request, $artifact, $redirect);
    }

    private function getRedirect(Codendi_Request $request, PFUser $current_user, Artifact $artifact)
    {
        $redirect = $this->redirectUrlAfterArtifactSubmission($request, $this->tracker->getId(), $artifact->getId());
        $this->redirectToParentCreationIfNeeded($artifact, $current_user, $redirect, $request);
        $artifact->summonArtifactRedirectors($request, $redirect);
        return $redirect;
    }

    private function executeRedirect(Codendi_Request $request, Artifact $artifact, Tracker_Artifact_Redirect $redirect)
    {
        if ($request->isAjax()) {
            header(JSONHeader::getHeaderForPrototypeJS(['aid' => $artifact->getId()]));
            exit;
        } elseif ($this->isFromOverlay($request)) {
            $purifier  = Codendi_HTMLPurifier::instance();
            $csp_nonce = $GLOBALS['Response']->getCSPNonce();
            echo sprintf('<script type="text/javascript" nonce="%s">window.parent.codendi.tracker.artifact.artifactLink.newArtifact(%d);</script>', $purifier->purify($csp_nonce), $artifact->getId());
            exit;
        } else {
            $GLOBALS['Response']->addFeedback('info', sprintf(dgettext('tuleap-tracker', 'Artifact Successfully Created (%1$s)'), $artifact->fetchXRefLink()), CODENDI_PURIFIER_LIGHT);
            $GLOBALS['Response']->redirect($redirect->toUrl());
        }
    }

    private function isFromOverlay(Codendi_Request $request)
    {
        if ($request->existAndNonEmpty('link-artifact-id') && ! $request->exist('immediate')) {
            return true;
        }
        return false;
    }

    protected function redirectToParentCreationIfNeeded(
        Artifact $artifact,
        PFUser $current_user,
        Tracker_Artifact_Redirect $redirect,
        Codendi_Request $request,
    ): void {
        $parent_tracker = $this->tracker->getParent();
        if ($parent_tracker && count($artifact->getAllAncestors($current_user)) == 0) {
            $art_link = $this->formelement_factory->getAnArtifactLinkField($current_user, $parent_tracker);

            if ($art_link && $this->isParentCreationRequested($request, $current_user)) {
                $art_link_key               = 'artifact[' . $art_link->getId() . '][new_values]';
                $art_link_type              = 'artifact[' . $art_link->getId() . '][type]';
                $redirect_params            = [
                    'tracker'      => (string) $parent_tracker->getId(),
                    'func'         => 'new-artifact',
                    $art_link_key  => (string) $artifact->getId(),
                    $art_link_type => urlencode(Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD),
                ];
                $redirect->mode             = Tracker_Artifact_Redirect::STATE_CREATE_PARENT;
                $redirect->query_parameters = $redirect_params;
            }
        }
    }

    private function isParentCreationRequested(Codendi_Request $request, PFUser $current_user): bool
    {
        $request_data        = $request->get('artifact');
        $artifact_link_field = $this->formelement_factory->getAnArtifactLinkField($current_user, $this->tracker);

        if (! $artifact_link_field) {
            return false;
        }

        $art_link_id = $artifact_link_field->getId();

        if (isset($request_data[$art_link_id]) && isset($request_data[$art_link_id]['parent'])) {
            return $request_data[$art_link_id]['parent'] == [Tracker_FormElement_Field_ArtifactLink::CREATE_NEW_PARENT_VALUE];
        }

        return false;
    }

    protected function redirectUrlAfterArtifactSubmission(Codendi_Request $request, $tracker_id, $artifact_id)
    {
        $redirect           = new Tracker_Artifact_Redirect();
        $redirect->base_url = TRACKER_BASE_URL;

        $stay     = $request->get('submit_and_stay');
        $continue = $request->get('submit_and_continue');
        if ($stay) {
            $redirect->mode = Tracker_Artifact_Redirect::STATE_STAY;
        } elseif ($continue) {
            $redirect->mode = Tracker_Artifact_Redirect::STATE_CONTINUE;
        } else {
            $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;
        }
        $redirect->query_parameters = $this->calculateRedirectParams($tracker_id, $artifact_id, $stay, $continue);

        return $redirect;
    }

    private function calculateRedirectParams($tracker_id, $artifact_id, $stay, $continue)
    {
        $redirect_params            = [];
        $redirect_params['tracker'] = $tracker_id;
        if ($continue) {
            $redirect_params['func'] = 'new-artifact';
        }
        if ($stay) {
            $redirect_params['aid'] = $artifact_id;
        }
        return array_filter($redirect_params);
    }
}
