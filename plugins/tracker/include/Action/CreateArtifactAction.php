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

declare(strict_types=1);

namespace Tuleap\Tracker\Action;

use Codendi_HTMLPurifier;
use Codendi_Request;
use PFUser;
use Tracker;
use Tracker_Artifact_Redirect;
use Tracker_ArtifactFactory;
use Tracker_Exception;
use Tracker_FormElementFactory;
use Tracker_IDisplayTrackerLayout;
use Tracker_IFetchTrackerSwitcher;
use Tuleap\JSONHeader;
use Tuleap\Request\RequestTime;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ArtifactDoesNotExistException;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\BuildInitialChangesetValuesContainer;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\Artifact\Link\ArtifactLinker;
use Tuleap\Tracker\Artifact\Link\ForwardLinkProxy;
use Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext;
use Tuleap\Tracker\FormElement\ArtifactLinkFieldDoesNotExistException;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\Hierarchy\ParentInHierarchyRetriever;
use Tuleap\Tracker\Permission\VerifySubmissionPermissions;
use Tuleap\Tracker\Semantic\SemanticNotSupportedException;

class CreateArtifactAction
{
    public function __construct(
        private readonly Tracker $tracker,
        private readonly TrackerArtifactCreator $artifact_creator,
        private readonly Tracker_ArtifactFactory $artifact_factory,
        private readonly Tracker_FormElementFactory $formelement_factory,
        private readonly VerifySubmissionPermissions $submission_permissions,
        private readonly ArtifactLinker $artifact_linker,
        private readonly ParentInHierarchyRetriever $parent_retriever,
        private readonly BuildInitialChangesetValuesContainer $initial_changeset_values_container_builder,
    ) {
    }

    /**
     * @throws SemanticNotSupportedException
     * @throws ArtifactDoesNotExistException
     * @throws ArtifactLinkFieldDoesNotExistException
     * @throws FieldValidationException
     * @throws Tracker_Exception
     */
    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $current_user): void
    {
        if ($this->submission_permissions->canUserSubmitArtifact($current_user, $this->tracker)) {
            $this->processCreate($layout, $request, $current_user);
        } else {
            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker', 'Access denied. You don\'t have permissions to perform this action.'));
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId());
        }
    }

    /**
     * @throws ArtifactDoesNotExistException
     * @throws SemanticNotSupportedException
     * @throws FieldValidationException
     * @throws ArtifactLinkFieldDoesNotExistException
     * @throws Tracker_Exception
     */
    private function processCreate(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $current_user): void
    {
        $link     = (int) $request->get('link-artifact-id');
        $artifact = $this->createArtifact($request, $current_user);
        if ($artifact !== null) {
            $this->associateImmediatelyIfNeeded($artifact, $request, $current_user);
            $this->redirect($request, $current_user, $artifact);
        }
        assert($layout instanceof Tracker_IFetchTrackerSwitcher);
        $this->tracker->displaySubmit($layout, $request, $current_user, $link);
    }

    /**
     * Add an artifact in the tracker
     *
     * @throws ArtifactDoesNotExistException
     * @throws ArtifactLinkFieldDoesNotExistException
     * @throws SemanticNotSupportedException
     * @throws Tracker_Exception
     * @throws FieldValidationException
     */
    private function createArtifact(Codendi_Request $request, PFUser $user): ?Artifact
    {
        $fields_data = $request->get('artifact');
        if (! isset($fields_data['request_method_called'])) {
            $fields_data['request_method_called'] = $request->get('func');
        }

        $this->tracker->augmentDataFromRequest($fields_data);

        return $this->artifact_creator->create(
            $this->tracker,
            $this->initial_changeset_values_container_builder->buildInitialChangesetValuesContainer($fields_data, $this->tracker, $user),
            $user,
            RequestTime::getTimestamp(),
            true,
            true,
            new NullChangesetValidationContext(),
            true,
        );
    }

    protected function associateImmediatelyIfNeeded(Artifact $new_artifact, Codendi_Request $request, PFUser $current_user): void
    {
        $link_artifact_id   = (int) $request->get('link-artifact-id');
        $is_immediate       = (bool) $request->get('immediate');
        $artifact_link_type = $request->get('link-type') ?: '';

        if (! $link_artifact_id || ! $is_immediate) {
            return;
        }

        $source_artifact = $this->artifact_factory->getArtifactById($link_artifact_id);
        if (! $source_artifact) {
            return;
        }

        $this->artifact_linker->linkArtifact(
            $source_artifact,
            new CollectionOfForwardLinks([
                ForwardLinkProxy::buildFromData($new_artifact->getId(), $artifact_link_type ?: ArtifactLinkField::NO_TYPE),
            ]),
            $current_user,
        );
    }

    private function redirect(Codendi_Request $request, PFUser $current_user, Artifact $artifact): void
    {
        $redirect = $this->getRedirect($request, $current_user, $artifact);
        $this->executeRedirect($request, $artifact, $redirect);
    }

    private function getRedirect(Codendi_Request $request, PFUser $current_user, Artifact $artifact): Tracker_Artifact_Redirect
    {
        $redirect = $this->redirectUrlAfterArtifactSubmission($request, $this->tracker->getId(), $artifact->getId());
        $this->redirectToParentCreationIfNeeded($artifact, $current_user, $redirect, $request);
        $artifact->summonArtifactRedirectors($request, $redirect);
        return $redirect;
    }

    private function executeRedirect(Codendi_Request $request, Artifact $artifact, Tracker_Artifact_Redirect $redirect): void
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

    private function isFromOverlay(Codendi_Request $request): bool
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
        $this->parent_retriever->getParentTracker($this->tracker)->apply(
            function (Tracker $parent_tracker) use ($artifact, $current_user, $redirect, $request) {
                if (count($artifact->getAllAncestors($current_user)) === 0) {
                    $art_link = $this->formelement_factory->getAnArtifactLinkField($current_user, $parent_tracker);

                    if ($art_link !== null && $this->isParentCreationRequested($request, $current_user)) {
                        $art_link_key               = 'artifact[' . $art_link->getId() . '][new_values]';
                        $art_link_type              = 'artifact[' . $art_link->getId() . '][type]';
                        $redirect_params            = [
                            'tracker'      => (string) $parent_tracker->getId(),
                            'func'         => 'new-artifact',
                            $art_link_key  => (string) $artifact->getId(),
                            $art_link_type => urlencode(ArtifactLinkField::TYPE_IS_CHILD),
                        ];
                        $redirect->mode             = Tracker_Artifact_Redirect::STATE_CREATE_PARENT;
                        $redirect->query_parameters = $redirect_params;
                    }
                }
            }
        );
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
            return $request_data[$art_link_id]['parent'] == [ArtifactLinkField::CREATE_NEW_PARENT_VALUE];
        }

        return false;
    }

    protected function redirectUrlAfterArtifactSubmission(Codendi_Request $request, int $tracker_id, int $artifact_id): Tracker_Artifact_Redirect
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

    private function calculateRedirectParams(int $tracker_id, int $artifact_id, $stay, $continue): array
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
