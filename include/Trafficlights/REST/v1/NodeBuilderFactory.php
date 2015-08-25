<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * MERCHANTABILITY or FITNEsemantic_status FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Trafficlights\REST\v1;

use Tracker_ResourceDoesntExistException;
use Tuleap\REST\ProjectAuthorization;
use Tracker_ArtifactFactory;
use Tracker_Artifact;
use Tracker_URLVerification;
use Tracker_ArtifactDao;
use PFUser;
use Tuleap\Trafficlights\Dao as TrafficlightsDao;

class NodeBuilderFactory {

    /** @var TrafficlightsDao */
    private $dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var ArtifactNodeBuilder */
    private $artifact_builder;

    /** @var ExecutionNodeBuilder */
    private $execution_builder;

    public function __construct() {
        $this->artifact_factory = Tracker_ArtifactFactory::instance();
        $this->dao              = new TrafficlightsDao();
        $this->artifact_builder = new ArtifactNodeBuilder(
            $this->artifact_factory,
            new Tracker_ArtifactDao(),
            new ArtifactNodeDao(),
            $this
        );
    }

    public function getNodeRepresentation(PFUser $user, Tracker_Artifact $artifact) {
        return $this->artifact_builder->getNodeRepresentation($user, $artifact);
    }


    /**
     * @param int $id
     *
     * @return Tracker_Artifact
     */
    public function getArtifactById(PFUser $user, $id) {
        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $id);
        if ($artifact) {
            ProjectAuthorization::userCanAccessProject(
                $user,
                $artifact->getTracker()->getProject(),
                new Tracker_URLVerification()
            );
            return $artifact;
        }
        throw new Tracker_ResourceDoesntExistException();
    }
}
