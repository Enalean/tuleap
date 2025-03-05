<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All rights reserved
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

use Tuleap\REST\MilestoneBase;

/**
 * @group MilestonesTest
 */
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class MilestonesMilestonesTest extends MilestoneBase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public function testPUTRemoveSubMilestones(): void
    {
        $response_put = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'milestones/' . $this->release_artifact_ids[1] . '/milestones'
            )->withBody($this->stream_factory->createStream('[]'))
        );
        $this->assertEquals($response_put->getStatusCode(), 200);
        $response_get  = $this->getResponse($this->request_factory->createRequest('GET', 'milestones/' . $this->release_artifact_ids[1] . '/milestones'));
        $submilestones = json_decode($response_get->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(0, $submilestones);
    }

    public function testPUTSubMilestoneDeniedForRESTReadOnlyUserNotInvolvedInProject(): void
    {
        $response_put = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'milestones/' . $this->release_artifact_ids[1] . '/milestones'
            )->withBody(
                $this->stream_factory->createStream(
                    '[' . $this->sprint_artifact_ids[1] . ']'
                )
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_put->getStatusCode());
    }

    public function testPUTOnlyOneSubMilestone(): void
    {
        $response_put = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'milestones/' . $this->release_artifact_ids[1] . '/milestones'
            )->withBody($this->stream_factory->createStream('[' . $this->sprint_artifact_ids[1] . ']'))
        );
        $this->assertEquals(200, $response_put->getStatusCode());

        $response_get  = $this->getResponse($this->request_factory->createRequest('GET', 'milestones/' . $this->release_artifact_ids[1] . '/milestones'));
        $submilestones = json_decode($response_get->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(1, $submilestones);
        $this->assertEquals($this->sprint_artifact_ids[1], $submilestones[0]['id']);
    }

    /**
     * @depends testPUTOnlyOneSubMilestone
     */
    public function testPUTOnlyOneSubMilestoneAlreadyAdded(): void
    {
        $response_put = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'milestones/' . $this->release_artifact_ids[1] . '/milestones'
            )->withBody(
                $this->stream_factory->createStream(
                    '[' . $this->sprint_artifact_ids[1] . ']'
                )
            )
        );

        $this->assertEquals(200, $response_put->getStatusCode());
    }

    public function testPUTOnlyOneSubMilestoneTwice(): void
    {
        $response_put = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'milestones/' . $this->release_artifact_ids[1] . '/milestones'
            )->withBody(
                $this->stream_factory->createStream(
                    '[' . $this->sprint_artifact_ids[1] . ',' . $this->sprint_artifact_ids[1] . ']'
                )
            )
        );

        $this->assertEquals(400, $response_put->getStatusCode());
    }
}
