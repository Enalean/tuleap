<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\Trafficlights\REST\v1;

use Tracker_Artifact;
use Tracker_FormElementFactory;
use Tuleap\Trafficlights\REST\v1\ExecutionRepresentation;
use PFUser;

class CampaignRepresentation {

    const ROUTE           = 'trafficlights_campaigns';
    const ASSIGNEES_ROUTE = 'trafficlights_assignees';

    const FIELD_NAME           = "label";
    const FIELD_ARTIFACT_LINKS = "artifact_links";
    const FIELD_STATUS         = "status";

    const STATUS_NOT_RUN = "notrun";
    const STATUS_PASSED  = "passed";
    const STATUS_FAILED  = "failed";
    const STATUS_BLOCKED = "blocked";

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    /** @var int */
    private $tracker_id;

    /** @var Tracker_Artifact */
    private $artifact;

    /** @var PFUser */
    private $user;

    /** @var int ID of the artifact */
    public $id;

    /** @var String Label of the campaign */
    public $label;

    /** @var String Status of the campaign */
    public $status;

    /** @var String */
    public $uri;

    /** @var int */
    public $nb_of_notrun;

    /** @var int */
    public $nb_of_passed;

    /** @var int */
    public $nb_of_failed;

    /** @var int */
    public $nb_of_blocked;

    /** @var array */
    public $resources;

    public function build(Tracker_Artifact $artifact, Tracker_FormElementFactory $form_element_factory, PFUser $user) {
        $this->artifact             = $artifact;
        $this->tracker_id           = $artifact->getTrackerId();
        $this->form_element_factory = $form_element_factory;
        $this->user                 = $user;

        $this->id                   = $artifact->getId();
        $this->uri                  = self::ROUTE . '/' . $this->id;
        $this->label                = $this->getFieldValue(self::FIELD_NAME)->getText();
        $this->status               = $this->artifact->getStatus();

        $executions_status          = $this->getExecutionsStatus();

        $this->nb_of_notrun         = $executions_status[self::STATUS_NOT_RUN];
        $this->nb_of_passed         = $executions_status[self::STATUS_PASSED];
        $this->nb_of_failed         = $executions_status[self::STATUS_FAILED];
        $this->nb_of_blocked        = $executions_status[self::STATUS_BLOCKED];
        $this->total                = $this->nb_of_notrun + $this->nb_of_passed + $this->nb_of_failed + $this->nb_of_blocked;

        $this->resources = array(
            array(
                'type' => ExecutionRepresentation::ROUTE,
                'uri'  => self::ROUTE . '/' . $this->id . '/' . ExecutionRepresentation::ROUTE
            ),
            array(
                'type' => self::ASSIGNEES_ROUTE,
                'uri'  => self::ROUTE . '/' . $this->id . '/' . self::ASSIGNEES_ROUTE
            )
        );
    }

    private function getFieldValue($field_shortname) {
        $field = $this->form_element_factory->getUsedFieldByNameForUser($this->tracker_id, $field_shortname, $this->user);

        return $this->artifact->getValue($field);
    }

    private function getExecutionsStatus() {
        $executions = array (
            self::STATUS_NOT_RUN => 0,
            self::STATUS_BLOCKED => 0,
            self::STATUS_PASSED  => 0,
            self::STATUS_FAILED  => 0
        );
        $linked_artifacts = $this->artifact->getLinkedArtifacts($this->user);

        foreach($linked_artifacts as $artifact) {
            if (isset($executions[$artifact->getStatus()])) {
                $executions[$artifact->getStatus()] ++;
            }
        }

        return $executions;
    }
}
