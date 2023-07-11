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

/**
 * @psalm-immutable
 */
class CompleteTrackerRepresentation implements TrackerRepresentation
{
    public const ROUTE = 'trackers';

    public const FULL_REPRESENTATION = 'full';

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
    public $fields = [];

    /**
     * @var array {@type Tuleap\Tracker\REST\StructureElementRepresentation
     */
    public $structure = [];

    /**
     * @var array {@type Tuleap\Tracker\REST\SemanticRepresentation}
     * @psalm-var object{string: \Tuleap\Tracker\REST\SemanticRepresentation}|non-empty-array<string,\Tuleap\Tracker\REST\SemanticRepresentation>
     */
    public object|array $semantics;

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

    private function __construct(
        Tracker $tracker,
        \Project $tracker_project,
        ?Tracker $tracker_parent,
        array $tracker_fields,
        array $structure,
        array $semantics,
        ?WorkflowRepresentation $workflow = null,
        ?PermissionsRepresentation $permissions = null,
    ) {
        $this->id       = JsonCast::toInt($tracker->getId());
        $this->uri      = self::ROUTE . '/' . $this->id;
        $this->html_url = $tracker->getUri();

        $this->project = new ProjectReference($tracker_project);

        $this->label                  = $tracker->getName();
        $this->description            = $tracker->getDescription();
        $this->item_name              = $tracker->getItemName();
        $this->fields                 = $tracker_fields;
        $this->structure              = $structure;
        $this->semantics              = JsonCast::toObject($semantics);
        $this->workflow               = $workflow;
        $this->resources              = [
            [
                'type' => 'reports',
                'uri'  => $this->uri . '/' . ReportRepresentation::ROUTE,
            ],
        ];
        $this->color_name             = $tracker->getColor()->getName();
        $this->permissions_for_groups = $permissions;

        if ($tracker_parent) {
            $this->parent = TrackerReference::build($tracker_parent);
        }
    }

    public static function build(Tracker $tracker, array $tracker_fields, array $structure, array $semantics, ?WorkflowRepresentation $workflow = null, ?PermissionsRepresentation $permissions = null): self
    {
        return new self(
            $tracker,
            $tracker->getProject(),
            $tracker->getParent(),
            $tracker_fields,
            $structure,
            $semantics,
            $workflow,
            $permissions,
        );
    }
}
