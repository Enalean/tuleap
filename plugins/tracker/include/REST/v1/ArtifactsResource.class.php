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
use \Tracker_Exception;
use \Tuleap\Tracker\REST\ChangesetCommentRepresentation;
use \Tuleap\Tracker\REST\TrackerReference;
use \Tracker_NoChangeException;
use \TrackerFactory;
use \Tracker_REST_Artifact_ArtifactCreator;
use \Tuleap\Tracker\REST\Artifact\ArtifactReference;
use \Tracker_URLVerification;
use \Tracker_Artifact_Changeset as Changeset;

class ArtifactsResource {
    const MAX_LIMIT      = 50;
    const DEFAULT_LIMIT  = 10;
    const DEFAULT_OFFSET = 0;

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
     * @url OPTIONS {id}/changesets
     *
     * @param int $id Id of the artifact
     */
    protected function optionsArtifactChangesets($id) {
        $user     = UserManager::instance()->getCurrentUser();
        $artifact = $this->getArtifactById($user, $id);

        $this->sendAllowHeadersForChangesets($artifact);
        Header::sendOptionsPaginationHeaders(self::DEFAULT_LIMIT, self::DEFAULT_OFFSET, self::MAX_LIMIT);
    }

    /**
     * Get changesets
     *
     * Get the changesets of a given artifact
     *
     * @url GET {id}/changesets
     *
     * @param int    $id     Id of the artifact
     * @param string $fields Whether you want to fetch all fields or just comments {@from path}{@choice all,comments}
     * @param int    $limit  Number of elements displayed per page {@from path}{@min 1}
     * @param int    $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return array {@type Tuleap\Tracker\REST\ChangesetRepresentation}
     */
    protected function getArtifactChangesets($id, $fields = Changeset::FIELDS_ALL, $limit = 10, $offset = self::DEFAULT_OFFSET) {
        $user       = UserManager::instance()->getCurrentUser();
        $artifact   = $this->getArtifactById($user, $id);
        $changesets = $this->builder->getArtifactChangesetsRepresentation($user, $artifact, $fields, $offset, $limit);

        $this->sendAllowHeadersForChangesets($artifact);
        Header::sendPaginationHeaders($limit, $offset, $changesets->totalCount(), self::MAX_LIMIT);
        return $changesets->toArray();
    }

    /**
     * Get children
     *
     * Get the children of a given artifact
     *
     * @url GET {id}/children
     *
     * @param int    $id     Id of the artifact
     *
     * @return array {@type Tuleap\Tracker\REST\Artifact\ArtifactRepresentation}
     */
    protected function getArtifactChildren($id) {
        $user     = UserManager::instance()->getCurrentUser();
        $artifact = $this->getArtifactById($user, $id);
        $children = $artifact->getChildrenForUser($user);

        return $this->builder->getListOfArtifactRepresentationWithFieldValues($user, $children);
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
     *  <li>You can re-use the same document provided by /artifacts/:id route
     *      section. Even if it contains more data. The extra data/info will be ignored</li>
     *  <li>You don't need to set all 'values' of the artifact, you can restrict to the modified ones</li>
     *  <li>Examples:</li>
     *    <ol>
     *      <li>To update a file field, the value must be an array of the ids of the attachment you want to keep attached together with the new ones you want to attach.
     *          Each new file must correspond to valid /artifact_temporary_files/:id resource.
     *          A user can only add their own temporary files</li>
     *      <li>To empty a file field of its content, the value should be empty (value: []).</li>
     *    </ol>
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
        } catch (Tracker_NoChangeException $exception) {
            // Do nothing
        } catch (Tracker_Exception $exception) {
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                throw new RestException(500, $GLOBALS['Response']->getRawFeedback());
            }
            throw new RestException(500, $exception->getMessage());
        } catch (Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException $exception) {
            throw new RestException(500, $exception->getMessage());
        } catch (Tracker_Artifact_Attachment_FileNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        }
        $this->sendAllowHeadersForArtifact($artifact);
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        Header::allowOptionsPost();
    }

    /**
     * Create artifact
     *
     * Things to take into account:
     * <ol>
     *  <li>You don't need to set all 'values' of the artifact, you can pass only the ones you want to add,
     *      together with those that are required (depends on a given tracker's configuration).</li>
     *  <li>Example:
     *          To attach a file on a file field, the value must contain the ids of the attachements you want to add (eg. : {
     *                                             "field_id": 101,
     *                                             "value": [41, 42]
     *                                           })
     *          Note that 41 and 42 ids are provided by /artifact_temporary_files routes.
     *          A user can only add their own temporary files.
     *          To create a temporary file, use POST on /artifact_temporary_files.
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
        } catch (Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException $exception) {
            throw new RestException(500, $exception->getMessage());
        } catch (Tracker_Artifact_Attachment_FileNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        }
        $this->options();
    }

    /**
     * @param int $id
     *
     * @return Tracker_Artifact
     */
    private function getArtifactById(PFUser $user, $id) {
        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $id);
        if ($artifact) {
            ProjectAuthorization::userCanAccessProject($user, $artifact->getTracker()->getProject(), new Tracker_URLVerification());
            return $artifact;
        }
        throw new RestException(404);
    }

    private function sendAllowHeadersForChangesets(Tracker_Artifact $artifact) {
        $date = $artifact->getLastUpdateDate();
        Header::allowOptionsGet();
        Header::lastModified($date);
    }

    private function sendAllowHeadersForArtifact(Tracker_Artifact $artifact) {
        $date = $artifact->getLastUpdateDate();
        Header::allowOptionsGetPut();
        Header::lastModified($date);
    }
}
