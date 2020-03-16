<?php
/**
 * Copyright (c) Enalean, 2013 - present. All Rights Reserved.
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

use Tuleap\REST\JsonCast;
use Tracker;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\Tracker\REST\Tracker\PermissionsRepresentation;

class CompleteTrackerRepresentation implements TrackerRepresentation
{

    public const ROUTE = 'trackers';

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
     * @var ProjectReference
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
    public $fields = array();

    /**
     * @var array {@type Tuleap\Tracker\REST\StructureElementRepresentation
     */
    public $structure = array();

    /**
     * @var array {@type Tuleap\Tracker\REST\SemanticRepresentation}
     */
    public $semantics = array();

    /**
     * @var WorkflowRepresentation | null
     */
    public $workflow;

    /**
     * @var PermissionsRepresentation | null
     */
    public $permissions_for_groups;

    /**
     * @var TrackerReference
     */

    public $parent;

    /**
     * @var array
     */
    public $resources;

    /**
     * @var string
     */
    public $color_name;

    public function build(Tracker $tracker, array $tracker_fields, array $structure, array $semantics, ?WorkflowRepresentation $workflow = null, ?PermissionsRepresentation $permissions = null)
    {
        $this->id          = JsonCast::toInt($tracker->getId());
        $this->uri         = self::ROUTE . '/' . $this->id;
        $this->html_url    = $tracker->getUri();

        $this->project     = new ProjectReference();
        $this->project->build($tracker->getProject());

        $this->label       = $tracker->getName();
        $this->description = $tracker->getDescription();
        $this->item_name   = $tracker->getItemName();
        $this->fields      = $tracker_fields;
        $this->structure   = $structure;
        $this->semantics   = $semantics;
        $this->workflow    = $workflow;
        $this->resources   = array(
            array(
                'type' => 'reports',
                'uri'  => $this->uri . '/' . ReportRepresentation::ROUTE
            )
        );
        $this->color_name  = $tracker->getColor()->getName();
        $this->permissions_for_groups = $permissions;

        if ($tracker->getParent()) {
            $this->parent = new TrackerReference();
            $this->parent->build($tracker->getParent());
        }
    }
}
