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

use Planning;

/**
 * Basic representation of a planning
 */
class PlanningRepresentation {

    /** @var int */
    public $id;

    /** @var String */
    public $label;

    /** @var int */
    public $project_id;

    /** @var int */
    public $milestone_type_id;

    /** @var array */
    public $backlog_item_types_id;

    /** @var String */
    public $milestones_title;

    /** @var String */
    public $backlog_items_title;

    public function __construct(Planning $planning) {
        $this->id                    = $planning->getId();
        $this->label                 = $planning->getName();
        $this->project_id            = $planning->getGroupId();
        $this->milestone_type_id     = $planning->getPlanningTrackerId();
        $this->backlog_item_types_id = $planning->getBacklogTrackersIds();
        $this->milestones_title      = $planning->getPlanTitle();
        $this->backlog_items_title   = $planning->getBacklogTitle();
    }
}
