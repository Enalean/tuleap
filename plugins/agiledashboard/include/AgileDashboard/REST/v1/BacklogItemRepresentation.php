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

class BacklogItemRepresentation {

    const ROUTE = 'backlog_items';
    const ARTIFACT_ROUTE = 'artifact';
    const TRACKER_ROUTE = 'tracker';

    /** @var Int */
    public $id;

    /** @var String */
    public $label;

    /** @var String */
    public $status;

    /** @var int*/
    public $submitted_by;

    /** @var String */
    public $submitted_on;

    /** @var String */
    public $last_updated_on;

    /** @var String */
    public $url;

    /** @var int */
    public $project_id;

    /** @var int */
    public $configuration_id;

    /** @var int */
    public $initial_effort;

    /** @var int */
    public $artifact_id;

    /** @var String */
    public $artifact_url;

    public function __construct(\AgileDashBoard_BacklogItem $backlog_item) {
        $this->id                = $backlog_item->id();
        $this->label             = $backlog_item->title();
        $this->status            = $backlog_item->status();
        $this->url               = self::ROUTE.'/'.$this->id;
        $this->configuration_id  = $backlog_item->getArtifact()->getTrackerId();;
        $this->configuration_url = self::TRACKER_ROUTE.'/'.$this->configuration_id;
        $this->initial_effort    = $backlog_item->getInitialEffort();
        $this->artifact_id       = $backlog_item->getArtifact()->getId();
        $this->artifact_url       = self::ARTIFACT_ROUTE.'/'.$this->artifact_id;
    }
}
?>