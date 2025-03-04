<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All rights reserved
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

namespace Tuleap\Tracker\Tests\REST\Report;

require_once __DIR__ . '/../TrackerBase.php';

use Tuleap\Tracker\Tests\REST\TrackerBase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerReportTest extends TrackerBase
{
    public function testGETArtifactReportWithRendererTableValues(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'trackers/' . urlencode($this->tracker_reports_tracker_id) . '/tracker_reports')
        );

        self::assertEquals(200, $response->getStatusCode());

        $response_reports = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $report_id        = null;
        foreach ($response_reports as $response_report) {
            if ($response_report['label'] === 'Activities') {
                $report_id = $response_report['id'];
            }
        }

        if ($report_id === null) {
            self::fail('Expected Report ID not found.');
        }

        $report_artifacts_url      = 'tracker_reports/' . urlencode($report_id) . '/artifacts?values=from_table_renderer';
        $report_artifacts_response = $this->getResponse(
            $this->request_factory->createRequest('GET', $report_artifacts_url)
        );

        self::assertEquals(200, $report_artifacts_response->getStatusCode());

        $response_flat_representation = $this->getResponse(
            $this->request_factory->createRequest('GET', 'tracker_reports/' . urlencode($report_id) . '/artifacts?values=from_table_renderer&output_format=flat')
        );

        $report_artifacts_response_body_content = $report_artifacts_response->getBody()->getContents();

        self::assertEquals(200, $response_flat_representation->getStatusCode());
        self::assertJsonStringNotEqualsJsonString(
            $report_artifacts_response_body_content,
            $response_flat_representation->getBody()->getContents(),
        );

        $response_flat_representation = $this->getResponse(
            $this->request_factory->createRequest('GET', 'tracker_reports/' . urlencode($report_id) . '/artifacts?values=from_table_renderer&output_format=flat_with_semicolon_string_array')
        );
        self::assertEquals(200, $response_flat_representation->getStatusCode());
        self::assertJsonStringNotEqualsJsonString(
            $report_artifacts_response_body_content,
            $response_flat_representation->getBody()->getContents(),
        );
    }
}
