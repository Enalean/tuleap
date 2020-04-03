<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\TestManagement\Nature;

use Project;
use Tracker_Artifact;
use Tuleap\TestManagement\Config;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;

class NatureCoveredByOverrider
{
    /** @var Config */
    private $config;

    /**
     * @var ArtifactLinksUsageDao
     */
    private $dao;

    public function __construct(
        Config $config,
        ArtifactLinksUsageDao $dao
    ) {
        $this->config = $config;
        $this->dao    = $dao;
    }

    public function getOverridingNature(
        Project $project,
        Tracker_Artifact $to_artifact,
        array $new_linked_artifact_ids
    ): ?string {
        if ($this->dao->isTypeDisabledInProject($project->getID(), NatureCoveredByPresenter::NATURE_COVERED_BY)) {
            return null;
        }

        $to_tracker_id        = $to_artifact->getTrackerId();
        $test_def_tracker_id  = $this->config->getTestDefinitionTrackerId($project);
        $to_artifact_id       = $to_artifact->getId();
        $is_new_artifact_link = in_array($to_artifact_id, $new_linked_artifact_ids);

        if ($to_tracker_id === $test_def_tracker_id && $is_new_artifact_link) {
            return NatureCoveredByPresenter::NATURE_COVERED_BY;
        }

        return null;
    }
}
