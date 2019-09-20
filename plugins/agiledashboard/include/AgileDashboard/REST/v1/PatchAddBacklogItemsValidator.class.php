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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v1;

use Tracker_ArtifactFactory;

class PatchAddBacklogItemsValidator implements IValidateElementsToAdd
{

    private $backlog_item_artifact_id;

    /**
     * @var Tracker[]
     */
    private $allowed_trackers;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(Tracker_ArtifactFactory $artifact_factory, array $allowed_trackers, $backlog_item_artifact_id)
    {
        $this->artifact_factory = $artifact_factory;
        foreach ($allowed_trackers as $tracker) {
            $this->allowed_trackers[$tracker->getId()] = true;
        }
        $this->backlog_item_artifact_id = $backlog_item_artifact_id;
    }

    /**
     * @param array $to_add
     * @throws ArtifactCannotBeChildrenOfException
     */
    public function validate(array $to_add)
    {
        foreach ($to_add as $id) {
            $artifact = $this->artifact_factory->getArtifactById($id);
            if (! isset($this->allowed_trackers[$artifact->getTrackerId()])) {
                throw new ArtifactCannotBeChildrenOfException($this->backlog_item_artifact_id, $id);
            }
        }
    }
}
