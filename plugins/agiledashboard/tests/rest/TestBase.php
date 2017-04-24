<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
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
use REST_TestDataBuilder;

class TestBase extends RestBase
{
    protected $kanban_artifact_ids = array();

    public function setUp()
    {
        parent::setUp();

        $this->getKanbanArtifactIds();
    }

    private function getKanbanArtifactIds()
    {
        $query = http_build_query(
            array('order' => 'asc')
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->setup_client->get("trackers/$this->kanban_tracker_id/artifacts?$query")
        );

        $artifacts = $response->json();
        $index     = 1;
        foreach ($artifacts as $kanban_artifact) {
            $this->kanban_artifact_ids[$index] = $kanban_artifact['id'];
            $index++;
        }
    }
}
