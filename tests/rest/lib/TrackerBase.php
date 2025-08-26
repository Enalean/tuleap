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

namespace Tuleap\REST;

class TrackerBase extends RestBase
{
    protected $report_id;
    protected $report_uri;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->getReleaseArtifactIds();
        $this->getStoryArtifactIds();
        $this->getReportId();
    }

    private function getReportId()
    {
        if ($this->report_id && $this->report_uri) {
            return;
        }

        $response = $this->getResponse($this->request_factory->createRequest('GET', "trackers/$this->releases_tracker_id/tracker_reports"));
        $reports  = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        foreach ($reports as $report) {
            if ($report['label'] === 'Default') {
                $this->report_id  = $report['id'];
                $this->report_uri = $report['uri'];
            }
        }
    }
}
