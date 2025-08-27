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
use Tuleap\REST\RESTTestDataBuilder;
use Tuleap\REST\RestBase;

#[DisableReturnValueGenerationForTestDoubles]
#[Group('Regressions')]
final class ArtifactsCreationWithWrongWorkflowTest extends RestBase
{
    private TrackerRESTHelperFactory $tracker_test_helper;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->tracker_test_helper = new TrackerRESTHelperFactory(
            $this->request_factory,
            $this->stream_factory,
            $this->rest_request,
            $this->project_pbi_id,
            RESTTestDataBuilder::TEST_USER_1_NAME
        );
    }

    public function testPostArtifactFailsIfValueInSelectBoxIsNotValidRegardingWorkflow(): void
    {
        $tracker  = $this->tracker_test_helper->getTrackerRest('releases');
        $response = $tracker->createArtifact(
            [
                $tracker->getSubmitTextValue('Version Number', '0.1'),
                $tracker->getSubmitListValue('Progress', 'Delivered to customer'),
            ]
        );

        self::assertSame(400, $response['error']['code']);
    }

    public function testPostArtifactSuccededIfValueInSelectBoxIsValidRegardingWorkflow(): void
    {
        $tracker       = $this->tracker_test_helper->getTrackerRest('releases');
        $artifact_json = $tracker->createArtifact(
            [
                $tracker->getSubmitTextValue('Version Number', '0.1'),
                $tracker->getSubmitListValue('Progress', 'To be defined'),
            ]
        );

        self::assertArrayHasKey('id', $artifact_json);
    }
}
