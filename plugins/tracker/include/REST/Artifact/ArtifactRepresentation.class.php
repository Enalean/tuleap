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

namespace Tuleap\Tracker\REST\Artifact;

use Tuleap\Tracker\REST\TrackerRepresentation;
use Tuleap\REST\ResourceReference;
use Tracker_Artifact;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\Tracker\REST\ChangesetRepresentation;
use Tuleap\REST\JsonCast;

class ArtifactRepresentation {

    const ROUTE = 'artifacts';

    /**
     * @var int ID of the artifact
     */
    public $id;

    /**
     * @var string URI of the artifact
     */
    public $uri;

    /**
     * @var Tuleap\REST\ResourceReference Reference to tracker the artifact belongs to
     */
    public $tracker;

    /**
     * @var \Tuleap\Project\REST\ProjectReference ID of the project the artifact belongs to
     */
    public $project;

    /**
     * @var int ID of the user who created the first version of the artifact
     */
    public $submitted_by;
    
    /**
     * @var string Date, when the first version of the artifact was created
     */
    public $submitted_on;

    /**
     * @var string URL to access the artifact with the Web UI
     */
    public $html_url;

    /**
     * @var string URI to access the artifact history
     */
    public $changesets_uri;

    /**
     * @var array Field values
     */
    public $values = array();

    public function build(Tracker_Artifact $artifact, array $values) {
        $this->id             = JsonCast::toInt($artifact->getId());
        $this->uri            = self::ROUTE . '/' . $artifact->getId();

        $this->tracker        = new ResourceReference();
        $this->tracker->build($artifact->getTrackerId(), TrackerRepresentation::ROUTE);

        $this->project        = new ProjectReference();
        $this->project->build($artifact->getTracker()->getProject());

        $this->submitted_by   = JsonCast::toInt($artifact->getSubmittedBy());
        $this->submitted_on   = JsonCast::toDate($artifact->getSubmittedOn());
        $this->html_url       = $artifact->getUri();
        $this->changesets_uri = self::ROUTE . '/' .  $this->id . '/'. ChangesetRepresentation::ROUTE;
        $this->values         = $values;
    }
}

