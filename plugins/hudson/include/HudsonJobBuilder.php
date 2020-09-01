<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

use Http\Client\HttpAsyncClient;
use HudsonJobURLFileException;
use HudsonJobURLFileNotFoundException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;

class HudsonJobBuilder
{
    /**
     * @var RequestFactoryInterface
     */
    private $http_request_factory;
    /**
     * @var HttpAsyncClient
     */
    private $http_client;


    public function __construct(RequestFactoryInterface $http_request_factory, HttpAsyncClient $http_client)
    {
        $this->http_request_factory = $http_request_factory;
        $this->http_client          = $http_client;
    }

    /**
     * @return \HudsonJob
     * @throws \Http\Client\Exception
     * @throws HudsonJobURLFileException
     * @throws HudsonJobURLFileNotFoundException
     */
    public function getHudsonJob(MinimalHudsonJob $minimal_hudson_job)
    {
        return $this->getHudsonJobsWithException([$minimal_hudson_job])[0]->getHudsonJob();
    }

    /**
     * @param MinimalHudsonJob[] $minimal_hudson_jobs
     * @return HudsonJobLazyExceptionHandler[]
     */
    public function getHudsonJobsWithException(array $minimal_hudson_jobs)
    {
        $promises = [];
        foreach ($minimal_hudson_jobs as $id => $minimal_hudson_job) {
            $request       = $this->getHTTPRequest($minimal_hudson_job);
            $promise       = $this->http_client->sendAsyncRequest($request);
            $promises[$id] = $promise;
        }

        $hudson_jobs = [];
        foreach ($promises as $id => $promise) {
            try {
                $minimal_hudson_job = $minimal_hudson_jobs[$id];
                $response           = $promise->wait();
                $hudson_jobs[$id]   = new HudsonJobLazyExceptionHandler(
                    new \HudsonJob($minimal_hudson_job->getName(), $this->getXMLContent($minimal_hudson_job, $response)),
                    null
                );
            } catch (\Exception $ex) {
                $hudson_jobs[$id] = new HudsonJobLazyExceptionHandler(null, $ex);
            }
        }

        return $hudson_jobs;
    }

    /**
     * @return \Psr\Http\Message\RequestInterface
     */
    private function getHTTPRequest(MinimalHudsonJob $minimal_hudson_job)
    {
        $job_url = $minimal_hudson_job->getJobUrl();
        return $this->http_request_factory->createRequest('GET', $job_url);
    }

    /**
     * @return SimpleXMLElement
     * @throws HudsonJobURLFileException
     */
    private function getXMLContent(MinimalHudsonJob $minimal_hudson_job, ResponseInterface $http_response)
    {
        if ($http_response->getStatusCode() === 404) {
            throw new HudsonJobURLFileNotFoundException(
                sprintf(dgettext('tuleap-hudson', 'File not found at URL: %1$s'), $minimal_hudson_job->getJobUrl())
            );
        }

        $previous_libxml_use_errors = libxml_use_internal_errors(true);
        $xmlobj                     = simplexml_load_string($http_response->getBody());
        libxml_use_internal_errors($previous_libxml_use_errors);
        if ($xmlobj !== false) {
            return $xmlobj;
        }
        throw new HudsonJobURLFileException(
            sprintf(dgettext('tuleap-hudson', 'Unable to read file at URL: %1$s'), $minimal_hudson_job->getJobUrl())
        );
    }
}
