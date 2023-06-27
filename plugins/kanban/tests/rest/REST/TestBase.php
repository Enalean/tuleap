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

namespace Tuleap\Kanban\REST;

use REST_TestDataBuilder;
use RestBase;

class TestBase extends RestBase
{
    protected $kanban_artifact_ids = [];
    protected $tracker_report_id   = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->getKanbanArtifactIds();

        $this->tracker_report_id = $this->getTrackerReportId();
    }

    private function getKanbanArtifactIds()
    {
        $this->getArtifactIds(
            $this->kanban_tracker_id,
            $this->kanban_artifact_ids
        );
    }

    private function getTrackerReportId()
    {
        if ($this->tracker_report_id !== null) {
            return $this->tracker_report_id;
        }

        $offset = 0;
        $limit  = 1;
        $query  = http_build_query(
            ['limit' => $limit, 'offset' => $offset]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', "trackers/$this->kanban_tracker_id/tracker_reports?$query")
        );

        $reports = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $reports[0]['id'];
    }
}
