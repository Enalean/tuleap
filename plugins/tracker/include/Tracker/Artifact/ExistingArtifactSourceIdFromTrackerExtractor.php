<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\Artifact;

use Tracker;
use Tuleap\Tracker\DAO\TrackerArtifactSourceIdDao;

class ExistingArtifactSourceIdFromTrackerExtractor
{
    /**
     * @var TrackerArtifactSourceIdDao
     */
    private $source_artifact_id_dao;

    public function __construct(TrackerArtifactSourceIdDao $source_artifact_id_dao)
    {
        $this->source_artifact_id_dao = $source_artifact_id_dao;
    }

    /**
     * @param $source_platform
     * @return array
     */
    public function getSourceArtifactIds(Tracker $tracker, $source_platform)
    {
        $source_artifact_ids = [];

        if ($source_platform === null) {
            return [];
        }

        $list_source_artifacts_ids = $this->source_artifact_id_dao->getSourceArtifactIds($source_platform, $tracker->getId());

        if ($list_source_artifacts_ids === null) {
            return [];
        }

        foreach ($list_source_artifacts_ids as $list_source_artifact_id) {
            $source_artifact_ids[$list_source_artifact_id['source_artifact_id']] = $list_source_artifact_id['artifact_id'];
        }

        return $source_artifact_ids;
    }
}
