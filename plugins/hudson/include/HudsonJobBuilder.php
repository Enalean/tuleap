<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Hudson;

use Http_ClientException;
use HudsonJobURLFileException;
use HudsonJobURLFileNotFoundException;
use SimpleXMLElement;

class HudsonJobBuilder
{
    const MAX_BATCH_EXECUTION_TIME = 5;

    /**
     * @var \Http_Client
     */
    private $http_client;

    public function __construct(\Http_Client $http_client)
    {
        $this->http_client = $http_client;
    }

    /**
     * @return \HudsonJob
     * @throws Http_ClientException
     * @throws HudsonJobURLFileException
     * @throws HudsonJobURLFileNotFoundException
     */
    public function getHudsonJob(MinimalHudsonJob $minimal_hudson_job)
    {
        return new \HudsonJob(
            $minimal_hudson_job->getName(),
            $this->getXMLContent($minimal_hudson_job->getJobUrl())
        );
    }

    /**
     * @param MinimalHudsonJob[]
     * @return HudsonJobLazyExceptionHandler[]
     */
    public function getHudsonJobsWithException(array $minimal_hudson_jobs)
    {
        $start_time  = time();
        $hudson_jobs = [];
        foreach ($minimal_hudson_jobs as $id => $minimal_hudson_job) {
            if ((time() - $start_time) >= self::MAX_BATCH_EXECUTION_TIME) {
                $hudson_jobs[$id] = new HudsonJobLazyExceptionHandler(
                    null,
                    new HudsonJobRetrievalTooLongException(
                        dgettext('tuleap-hudson', 'Jobs retrieval took too long, this job has been ignored')
                    )
                );
                continue;
            }
            try {
                $hudson_jobs[$id] = new HudsonJobLazyExceptionHandler($this->getHudsonJob($minimal_hudson_job), null);
            } catch (\Exception $exception) {
                $hudson_jobs[$id] = new HudsonJobLazyExceptionHandler(null, $exception);
            }
        }

        return $hudson_jobs;
    }

    /**
     * @return SimpleXMLElement
     * @throws Http_ClientException
     * @throws HudsonJobURLFileException
     * @throws HudsonJobURLFileNotFoundException
     */
    private function getXMLContent($job_url)
    {
        $this->http_client->setOption(CURLOPT_URL, $job_url);
        $this->http_client->doRequest();

        $xmlstr = $this->http_client->getLastResponse();
        if ($xmlstr !== false) {
            $previous_libxml_use_errors = libxml_use_internal_errors(true);
            $xmlobj = simplexml_load_string($xmlstr);
            libxml_use_internal_errors($previous_libxml_use_errors);
            if ($xmlobj !== false) {
                return $xmlobj;
            }
            throw new HudsonJobURLFileException($GLOBALS['Language']->getText('plugin_hudson', 'job_url_file_error', [$job_url]));
        }
        throw new HudsonJobURLFileNotFoundException($GLOBALS['Language']->getText('plugin_hudson', 'job_url_file_not_found', [$job_url]));
    }
}
