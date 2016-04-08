<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\HudsonGit;

class PollingResponseFactory
{

    public function buildResponseFormCurl($response, $header_size)
    {
        $jenkins_job = new PollingResponse();
        $header      = substr($response, 0, $header_size);
        $jenkins_job->setBody(substr($response, $header_size));

        foreach (explode("\r\n", $header) as $line_header) {
            if (preg_match_all('/^Triggered: (.+)/', $line_header, $job_list)) {
                foreach ($job_list[1] as $job_path) {
                    $jenkins_job->addJob($job_path);
                }
            }
        }

        return $jenkins_job;
    }
}
