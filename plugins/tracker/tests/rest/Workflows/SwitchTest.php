<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Tests\REST\Workflows;

use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ . '/../bootstrap.php';

class SwitchTest extends TrackerBase
{
    public function testPATCHTrackerToSwitchWorkflowInAdvancedMode(): array
    {
        $tracker = $this->tracker_representations[$this->simple_mode_workflow_to_switch_tracker_id];
        $workflow = $tracker['workflow'];

        $this->assertFalse($workflow['is_advanced']);

        $query          = '{"workflow": {"is_advanced": true}}';
        $response_patch = $this->getResponseByName(
            \REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->patch("trackers/" . $this->simple_mode_workflow_to_switch_tracker_id . '?query=' . urlencode($query), null, null)
        );

        $this->assertSame(200, $response_patch->getStatusCode());

        $tracker_after_patch  = $response_patch->json();
        $workflow_after_patch = $tracker_after_patch['workflow'];

        $this->assertTrue($workflow_after_patch['is_advanced']);

        return $tracker_after_patch;
    }

    /**
     * @depends testPATCHTrackerToSwitchWorkflowInAdvancedMode
     */
    public function testPATCHTrackerToSwitchWorkflowInSimpleMode(array $tracker): void
    {
        $workflow = $tracker['workflow'];

        $this->assertTrue($workflow['is_advanced']);

        $query          = '{"workflow": {"is_advanced": false}}';
        $response_patch = $this->getResponseByName(
            \REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->patch("trackers/" . $this->simple_mode_workflow_to_switch_tracker_id . '?query=' . urlencode($query), null, null)
        );

        $this->assertSame(200, $response_patch->getStatusCode());

        $tracker_after_patch  = $response_patch->json();
        $workflow_after_patch = $tracker_after_patch['workflow'];

        $this->assertFalse($workflow_after_patch['is_advanced']);
    }
}
