<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v1;

use \Tuleap\REST\JsonCast;
use \Tuleap\Project\REST\ProjectReference;
use \Tuleap\Tracker\REST\Artifact\ArtifactReference;

class BacklogItemRepresentation {

    const ROUTE = 'backlog_items';

    /**
     * @var Int
     */
    public $id;

    /**
     * @var String
     */
    public $label;

    /**
     * @var String
     */
    public $type;

    /**
     * @var String
     */
    public $status;

    /**
     * @var Float
     */
    public $initial_effort;

    /**
     * @var \Tuleap\Tracker\REST\Artifact\ArtifactReference
     */
    public $artifact;

    /**
     * @var Tuleap\AgileDashboard\REST\v1\BacklogItemParentReference
     */
    public $parent;

    /**
     * @var Tuleap\Project\REST\ProjectReference
     */
    public $project;

    public function build(\AgileDashboard_Milestone_Backlog_IBacklogItem $backlog_item) {
        $this->id             = JsonCast::toInt($backlog_item->id());
        $this->label          = $backlog_item->title();
        $this->status         = $backlog_item->status();
        $this->type           = $backlog_item->type();
        $this->initial_effort = JsonCast::toFloat($backlog_item->getInitialEffort());

        $this->artifact = new ArtifactReference();
        $this->artifact->build($backlog_item->getArtifact());

        $this->project = new ProjectReference();
        $this->project->build($backlog_item->getArtifact()->getTracker()->getProject());

        $this->parent = null;
        if ($backlog_item->getParent()) {
            $this->parent = new BacklogItemParentReference();
            $this->parent->build($backlog_item->getParent());
        }
    }
}
