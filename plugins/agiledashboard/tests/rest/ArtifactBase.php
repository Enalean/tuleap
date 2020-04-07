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
 */

namespace Tuleap\AgileDashboard\REST;

require_once dirname(__FILE__) . '/bootstrap.php';

use Tuleap\REST\ArtifactBase as Base;

class ArtifactBase extends Base
{
    public const BURNUP_FIELD_SHORTNAME = 'burnup_field';

    protected $burnup_artifact_ids = array();
    private $burnup_tracker_id;

    public function setUp(): void
    {
        parent::setUp();

        $project                 = $this->getProjectId(DataBuilder::PROJECT_BURNUP_SHORTNAME);
        $this->burnup_tracker_id = $this->tracker_ids[$project][DataBuilder::RELEASE_TRACKER_SHORTNAME];

        $this->getBurnupArtifactIds();
    }

    protected function getBurnupArtifactIds()
    {
        $this->getArtifactIds(
            $this->burnup_tracker_id,
            $this->burnup_artifact_ids
        );
    }
}
