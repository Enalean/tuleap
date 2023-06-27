<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\AgileDashboard\REST;

use RestBase;

class TestBase extends RestBase
{
    private const EXPLICIT_BACKLOG_STORY_TRACKER_SHORTNAME   = 'story';
    private const EXPLICIT_BACKLOG_RELEASE_TRACKER_SHORTNAME = 'rel';

    protected $explicit_backlog_project_id;
    protected $explicit_backlog_story_tracker_id;
    protected $explicit_backlog_release_tracker_id;

    protected $explicit_backlog_artifact_story_ids   = [];
    protected $explicit_backlog_artifact_release_ids = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_project_id         = $this->getProjectId(DataBuilder::EXPLICIT_BACKLOG_PROJECT_SHORTNAME);
        $this->explicit_backlog_story_tracker_id   = $this->tracker_ids[$this->explicit_backlog_project_id][self::EXPLICIT_BACKLOG_STORY_TRACKER_SHORTNAME];
        $this->explicit_backlog_release_tracker_id = $this->tracker_ids[$this->explicit_backlog_project_id][self::EXPLICIT_BACKLOG_RELEASE_TRACKER_SHORTNAME];

        $this->getArtifactIds(
            $this->explicit_backlog_story_tracker_id,
            $this->explicit_backlog_artifact_story_ids
        );

        $this->getArtifactIds(
            $this->explicit_backlog_release_tracker_id,
            $this->explicit_backlog_artifact_release_ids
        );
    }
}
