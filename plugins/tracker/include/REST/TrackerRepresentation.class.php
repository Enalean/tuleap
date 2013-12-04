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

namespace Tuleap\Tracker\REST;

use Tracker;

class TrackerRepresentation {

    const ROUTE = 'trackers';

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $uri;

    /**
     * @var string
     */
    public $html_url;

    /**
     * @var Tuleap\REST\ResourceReference
     */
    public $project;

    /**
     * @var string
     */
    public $label;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $item_name;

    /**
     * @var array {@type Tracker_REST_FieldRepresentation}
     */
    public $fields    = array();

    /**
     * @var array {@type Tracker_REST_SemanticRepresentation}
     */
    public $semantics = array();

    /**
     * @var Tuleap\Tracker\REST\WorkflowRepresentation
     */
    public $workflow;

    public function build(Tracker $tracker, array $tracker_fields, array $semantics, WorkflowRepresentation $workflow = null) {
        $this->id          = $tracker->getId();
        $this->uri         = self::ROUTE . '/' . $this->id;
        $this->html_url    = $tracker->getUri();
        $this->project     = new \Tuleap\Project\REST\ProjectReference($tracker->getProject());
        $this->label       = $tracker->getName();
        $this->description = $tracker->getDescription();
        $this->item_name   = $tracker->getItemName();
        $this->fields      = $tracker_fields;
        $this->semantics   = $semantics;
        $this->workflow    = $workflow;
    }
}
