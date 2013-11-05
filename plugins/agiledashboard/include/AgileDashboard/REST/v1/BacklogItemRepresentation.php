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

use \Tracker_REST_Artifact_ArtifactRepresentation;
use \Tracker_REST_TrackerRepresentation;
use \AgileDashBoard_BacklogItem;
use \Rest_ResourceReference;

class BacklogItemRepresentation {

    const ROUTE = 'backlog_items';

    /** @var Int */
    public $id;

    /** @var String */
    public $label;

    /** @var String */
    public $status;

    /** @var int */
    public $initial_effort;

    /** @var Rest_ResourceReference */
    public $tracker;

    /** @var Rest_ResourceReference */
    public $artifact;

    /** @var Rest_ResourceReference */
    public $parent;

    public function __construct(AgileDashBoard_BacklogItem $backlog_item) {
        $this->id                = $backlog_item->id();
        $this->label             = $backlog_item->title();
        $this->status            = $backlog_item->status();
        $this->initial_effort    = $backlog_item->getInitialEffort();
        $this->tracker = new Rest_ResourceReference(
            $backlog_item->getArtifact()->getTrackerId(),
            Tracker_REST_TrackerRepresentation::ROUTE
        ) ;
        $this->artifact = new Rest_ResourceReference(
            $backlog_item->getArtifact()->getId(),
            Tracker_REST_Artifact_ArtifactRepresentation::ROUTE
        );
        if ($backlog_item->getParent()) {
            $this->parent = new Rest_ResourceReference(
                $backlog_item->getParent()->getId(),
                self::ROUTE
            );
        }
    }
}
