<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All Rights Reserved.
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

use PFUser;
use Tracker_Artifact;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\REST\ChangesetRepresentation;
use Tuleap\Tracker\REST\TrackerRepresentation;
use Tuleap\User\REST\MinimalUserRepresentation;

class ArtifactRepresentation
{

    public const ROUTE = 'artifacts';

    /**
     * @var int ID of the artifact {@type int} {@required true}
     */
    public $id;

    /**
     * @var string URI of the artifact {@type string} {@required true}
     */
    public $uri;

    /**
     * @var string The xref of the artifact (ex: bug #123) {@type string}
     */
    public $xref;

    /**
     * @var TrackerRepresentation  {@type Tuleap\Tracker\REST\TrackerRepresentation} {@required true}
     */
    public $tracker;

    /**
     * @var \Tuleap\Project\REST\ProjectReference ID of the project the artifact belongs to {@type Tuleap\Project\REST\ProjectReference} {@required true}
     */
    public $project;

    /**
     * @var int ID of the user who created the first version of the artifact {@type int} {@required true}
     */
    public $submitted_by;

    /**
     * @var Tuleap\User\REST\MinimalUserRepresentation the minimal user representation who created the first version of the artifact {@type Tuleap\User\REST\MinimalUserRepresentation} {@required true}
     */
    public $submitted_by_user;

    /**
     * @var string Date, when the first version of the artifact was created {@type string} {@required true}
     */
    public $submitted_on;

    /**
     * @var string URL to access the artifact with the Web UI {@type string} {@required true}
     */
    public $html_url;

    /**
     * @var string URI to access the artifact history {@type string} {@required true}
     */
    public $changesets_uri;

    /**
     * @var array Field values {@type array}
     */
    public $values = null;

    /**
     * @var array values by field {@type array}
     */
    public $values_by_field = null;

    /**
     * @var string Date, when the last modification occurs {@type string} {@required true}
     */
    public $last_modified_date;

    /**
     * @var the semantic status value {@type string}
     */
    public $status;

    /**
     * @var the semantic title value {@type string}
     */
    public $title;

    /**
     * @var the semantic assignee value {@type array}
     */
    public $assignees;

    public function build(PFUser $current_user, Tracker_Artifact $artifact, $values, $values_by_field, TrackerRepresentation $tracker_representation)
    {
        $this->id             = JsonCast::toInt($artifact->getId());
        $this->uri            = self::ROUTE . '/' . $artifact->getId();
        $this->xref           = $artifact->getXRef();
        $this->tracker        = $tracker_representation;
        $this->project        = new ProjectReference();
        $this->project->build($artifact->getTracker()->getProject());

        $this->submitted_by       = JsonCast::toInt($artifact->getSubmittedBy());
        $user = $artifact->getSubmittedByUser();
        $this->submitted_by_user = new MinimalUserRepresentation();
        $this->submitted_by_user->build($user);

        $this->submitted_on       = JsonCast::toDate($artifact->getSubmittedOn());
        $this->html_url           = $artifact->getUri();
        $this->changesets_uri     = self::ROUTE . '/' .  $this->id . '/' . ChangesetRepresentation::ROUTE;
        $this->values             = $values;
        $this->values_by_field    = $values_by_field;
        $this->last_modified_date = JsonCast::toDate($artifact->getLastUpdateDate());

        $this->status = $artifact->getStatus();
        $this->title  = $artifact->getTitle();

        $this->assignees = array();
        foreach ($artifact->getAssignedTo($current_user) as $assignee) {
            $user_representation = new MinimalUserRepresentation();
            $user_representation->build($assignee);
            $this->assignees[] = $user_representation;
        }
    }
}
