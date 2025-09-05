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

declare(strict_types=1);

namespace Tuleap\Tracker\REST\Regressions;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\Attributes\Group;
use Tuleap\REST\BaseTestDataBuilder;
use Tuleap\REST\RestBase;
use Tuleap\REST\Tests\API\ProjectsAPIHelper;
use Tuleap\Tracker\REST\Tests\TrackerRESTHelper;
use Tuleap\Tracker\REST\Tests\TrackerRESTHelperFactory;

#[DisableReturnValueGenerationForTestDoubles]
#[Group('Regressions')]
final class ArtifactsCreationWithWrongWorkflowTest extends RestBase
{
    private const string TRACKER_NAME = 'releases';

    public function testArtifactCreationWithWorkflow(): void
    {
        $projects_api   = new ProjectsAPIHelper($this->rest_request, $this->request_factory);
        $project_pbi_id = $projects_api->findProjectId(BaseTestDataBuilder::PROJECT_PBI_SHORTNAME);

        $tracker_factory = new TrackerRESTHelperFactory(
            $this->rest_request,
            $this->request_factory,
            $this->stream_factory,
            $project_pbi_id,
            BaseTestDataBuilder::TEST_USER_1_NAME
        );
        $tracker         = $tracker_factory->getTrackerRest(self::TRACKER_NAME);

        $this->assertCreateArtifactFailsIfValueInSelectBoxIsNotValidRegardingWorkflow($tracker);
        $this->assertCreateArtifactSuccessIfValueInSelectBoxIsValidRegardingWorkflow($tracker);
    }

    private function assertCreateArtifactFailsIfValueInSelectBoxIsNotValidRegardingWorkflow(
        TrackerRESTHelper $tracker,
    ): void {
        $this->expectException(\RuntimeException::class);
        $response = $tracker->createArtifact(
            [
                $tracker->getSubmitTextValue('version_number', '0.1'),
                $tracker->getSubmitListValue('progress', 'Delivered to customer'),
            ]
        );

        self::assertSame(400, $response['error']['code']);
    }

    private function assertCreateArtifactSuccessIfValueInSelectBoxIsValidRegardingWorkflow(
        TrackerRESTHelper $tracker,
    ): void {
        $artifact_json = $tracker->createArtifact(
            [
                $tracker->getSubmitTextValue('version_number', '0.1'),
                $tracker->getSubmitListValue('progress', 'To be defined'),
            ]
        );

        self::assertArrayHasKey('id', $artifact_json);
    }
}
