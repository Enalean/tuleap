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

use \Tuleap\REST\Header;
use \Luracast\Restler\RestException;
use \Tracker_ArtifactFactory;
use \Tracker_Artifact;
use \Project_AccessProjectNotFoundException;
use \Project_AccessException;
use \URLVerification;
use \UserManager;
use \PFUser;
use \Tracker_REST_Artifact_ArtifactRepresentationBuilder;
use \Tracker_FormElementFactory;

class ArtifactsResource {
    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_REST_Artifact_ArtifactRepresentationBuilder */
    private $builder;

    public function __construct() {
        $this->artifact_factory = Tracker_ArtifactFactory::instance();
        $this->builder          = new Tracker_REST_Artifact_ArtifactRepresentationBuilder(
            Tracker_FormElementFactory::instance()
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

        return $this->builder->getArtifactRepresentation($user, $artifact);
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
     * @param int $id
     *
     * @return Tracker_Artifact
     */
    private function getArtifactById(PFUser $user, $id) {
        try {
            $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $id);
            if ($artifact) {
                $url_verification = new URLVerification();
                $url_verification->userCanAccessProject($user, $artifact->getTracker()->getProject());
                return $artifact;
            }
            throw new RestException(404);
        } catch (Project_AccessProjectNotFoundException $exception) {
            throw new RestException(404);
        } catch (Project_AccessException $exception) {
            throw new RestException(403, $exception->getMessage());
        }
    }

    private function sendAllowHeadersForArtifact(Tracker_Artifact $artifact) {
        $date = $artifact->getLastUpdateDate();
        Header::allowOptionsGet();
        Header::lastModified($date);
    }
}
