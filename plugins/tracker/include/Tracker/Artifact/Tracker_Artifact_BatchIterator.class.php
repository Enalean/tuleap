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

class Tracker_Artifact_BatchIterator
{

    public const ITEMS_PER_BATCH = 100;

    private $batches_processed;

    /* Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    private $tracker_id;

    public function __construct(Tracker_ArtifactFactory $tracker_artifact_factory, $tracker_id)
    {
        $this->tracker_artifact_factory = $tracker_artifact_factory;
        $this->tracker_id               = $tracker_id;
    }

    /**
     * @return Tracker_Artifact[]
     */
    public function next()
    {
        $this->batches_processed++;

        return $this->current();
    }

    /**
     * @return Tracker_Artifact[]
     */
    public function current()
    {
        $offset = max(array(self::ITEMS_PER_BATCH * $this->batches_processed, 0));
        $limit  = self::ITEMS_PER_BATCH;

        $paginated_artifacts = $this->tracker_artifact_factory->getPaginatedArtifactsByTrackerId($this->tracker_id, $limit, $offset, false);
        return $paginated_artifacts->getArtifacts();
    }

    public function rewind()
    {
        $this->batches_processed = -1;
    }
}
