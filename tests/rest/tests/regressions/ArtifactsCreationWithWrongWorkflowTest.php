<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('Regressions')]
class ArtifactsCreationWithWrongWorkflowTest extends RestBase
{
    private \Test\Rest\Tracker\TrackerFactory $tracker_test_helper;

    public function setUp(): void
    {
        parent::setUp();

        $this->tracker_test_helper = new Test\Rest\Tracker\TrackerFactory(
            $this->request_factory,
            $this->stream_factory,
            $this->rest_request,
            $this->project_pbi_id,
            REST_TestDataBuilder::TEST_USER_1_NAME
        );
    }

    public function testPostArtifactFailsIfValueInSelectBoxIsNotValidRegardingWorkflow()
    {
        $tracker  = $this->tracker_test_helper->getTrackerRest('releases');
        $response = $tracker->createArtifact(
            [
                $tracker->getSubmitTextValue('Version Number', '0.1'),
                $tracker->getSubmitListValue('Progress', 'Delivered to customer'),
            ]
        );

        $this->assertEquals($response['error']['code'], 400);
    }

    public function testPostArtifactSuccededIfValueInSelectBoxIsValidRegardingWorkflow()
    {
        $tracker       = $this->tracker_test_helper->getTrackerRest('releases');
        $artifact_json = $tracker->createArtifact(
            [
                $tracker->getSubmitTextValue('Version Number', '0.1'),
                $tracker->getSubmitListValue('Progress', 'To be defined'),
            ]
        );

        $this->assertTrue(isset($artifact_json['id']));
    }
}
