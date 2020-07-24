<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v1;

use PFUser;
use Planning_Milestone;
use Tracker_ArtifactFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\IFilterValidElementsToUnkink;

class FilterValidContent implements IFilterValidElementsToUnkink
{
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    private $indexed_backlog_trackers;

    public function __construct(Tracker_ArtifactFactory $artifact_factory, Planning_Milestone $milestone)
    {
        $this->artifact_factory = $artifact_factory;
        foreach ($milestone->getPlanning()->getBacklogTrackersIds() as $tracker_id) {
            $this->indexed_backlog_trackers[$tracker_id] = true;
        }
    }

    public function filter(PFUser $user, array $artifact_ids_to_be_removed): array
    {
        $valid_artifact_ids = [];
        foreach ($artifact_ids_to_be_removed as $id) {
            $artifact = $this->artifact_factory->getArtifactById($id);
            if (isset($this->indexed_backlog_trackers[$artifact->getTrackerId()])) {
                $valid_artifact_ids[] = $id;
            }
        }
        return $valid_artifact_ids;
    }
}
