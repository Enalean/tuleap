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

namespace Tuleap\Tracker\REST\v1;

use \Tuleap\REST\ProjectAuthorization;
use \Tuleap\REST\Header;
use \Luracast\Restler\RestException;
use \Tracker_ArtifactFactory;
use \Tracker_Artifact;
use \UserManager;
use \PFUser;
use \Tracker_REST_Artifact_ArtifactRepresentationBuilder;
use \Tracker_FormElementFactory;
use \Tracker_REST_Artifact_ArtifactUpdater;
use \Tracker_REST_Artifact_ArtifactValidator;
use \Tracker_FormElement_InvalidFieldException;
use \Tracker_FormElement_NotImplementedForRESTException;
use \Tracker_Exception;
use \Tuleap\Tracker\REST\ChangesetCommentRepresentation;
use \Tuleap\Tracker\REST\TrackerReference;
use \Tracker_NoChangeException;
use \TrackerFactory;
use \Tracker_REST_Artifact_ArtifactCreator;
use \Tuleap\Tracker\REST\Artifact\ArtifactReference;

class ArtifactsResource {
    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_REST_Artifact_ArtifactRepresentationBuilder */
    private $builder;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var TrackerFactory */
    private $tracker_factory;

    public function __construct() {
        $this->tracker_factory     = TrackerFactory::instance();
        $this->formelement_factory = Tracker_FormElementFactory::instance();
        $this->artifact_factory    = Tracker_ArtifactFactory::instance();
        $this->builder             = new Tracker_REST_Artifact_ArtifactRepresentationBuilder(
            $this->formelement_factory
        );
    }

    /**
     * Get artifact
     *
     * Get the content of a given artifact. In addition of the artifact representation,
     * it sets Last-Modified header with the last update date of the element
     *
     * @url GET {id}
     *
     * @param int $id Id of the artifact
     *
     * @return Tuleap\Tracker\REST\Artifact\ArtifactRepresentation
     */
    protected function getId($id) {
        $user     = UserManager::instance()->getCurrentUser();
        $artifact = $this->getArtifactById($user, $id);
        $this->sendAllowHeadersForArtifact($artifact);

        return $this->builder->getArtifactRepresentationWithFieldValues($user, $artifact);
    }

    /**
     * Get changesets
     *
     * Get the changesets of a given artifact
     *
     * @url GET {id}/changesets
     *
     * @param int $id Id of the artifact
     *
     * @return array ArtifactChangesetsRepresentation
     */
    protected function getArtifactChangesets($id) {
        $user     = UserManager::instance()->getCurrentUser();
        $artifact = $this->getArtifactById($user, $id);
        $this->sendAllowHeadersForArtifact($artifact);

        return $this->builder->getArtifactChangesetsRepresentation($user, $artifact);
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id Id of the artifact
     */
    protected function optionsId($id) {
        $user     = UserManager::instance()->getCurrentUser();
        $artifact = $this->getArtifactById($user, $id);
        $this->sendAllowHeadersForArtifact($artifact);
    }

    /**
     * Update artifact
     *
     * Things to take into account:
     * <ol>
     *  <li>You will get an error (400) if there are no changes in submitted document</li>
     *  <li>Please note that "file" fields cannot be modified yet</li>
     *  <li>You can re-use the same document provided by /artifacts/:id route
     *      section. Even if it contains more data. The extra data/info will be ignored</li>
     *  <li>You don't need to set all 'values' of the artifact, you can restrict to the modified ones</li>
     * </ol>
     *
     * @url PUT {id}
     * @param string                          $id        Id of the artifact
     * @param array                           $values    Artifact fields values {@from body}
     * @param ChangesetCommentRepresentation  $comment   Comment about update {body, format} {@from body}
     *
     */
    protected function putId($id, array $values, ChangesetCommentRepresentation $comment = null) {
        try {
            $user     = UserManager::instance()->getCurrentUser();
            $artifact = $this->getArtifactById($user, $id);

            $updater = new Tracker_REST_Artifact_ArtifactUpdater(
                new Tracker_REST_Artifact_ArtifactValidator(
                    $this->formelement_factory
                )
            );
            $updater->update($user, $artifact, $values, $comment);
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_NotImplementedForRESTException $exception) {
            throw new RestException(501, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            // Do nothing
        } catch (Tracker_Exception $exception) {
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                throw new RestException(500, $GLOBALS['Response']->getRawFeedback());
            }
            throw new RestException(500, $exception->getMessage());
        }
        $this->sendAllowHeadersForArtifact($artifact);
    }

    /**
     * Create artifact
     *
     * Things to take into account:
     * <ol>
     *  <li>Please note that "file" fields cannot be modified yet</li>
     * </ol>
     *
     * @url POST
     * @param TrackerReference $tracker   Id of the artifact {@from body}
     * @param array  $values    Artifact fields values {@from body}
     * @return ArtifactReference
     */
    protected function post(TrackerReference $tracker, array $values) {
        try {
            $user    = UserManager::instance()->getCurrentUser();
            $updater = new Tracker_REST_Artifact_ArtifactCreator(
                new Tracker_REST_Artifact_ArtifactValidator(
                    $this->formelement_factory
                ),
                $this->artifact_factory,
                $this->tracker_factory
            );
            return $updater->create($user, $tracker, $values);
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_NotImplementedForRESTException $exception) {
            throw new RestException(501, $exception->getMessage());
        }
    }

    /**
     * @param int $id
     *
     * @return Tracker_Artifact
     */
    private function getArtifactById(PFUser $user, $id) {
        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $id);
        if ($artifact) {
            ProjectAuthorization::userCanAccessProject($user, $artifact->getTracker()->getProject());
            return $artifact;
        }
        throw new RestException(404);
    }

    private function sendAllowHeadersForArtifact(Tracker_Artifact $artifact) {
        $date = $artifact->getLastUpdateDate();
        Header::allowOptionsGetPut();
        Header::lastModified($date);
    }
}
