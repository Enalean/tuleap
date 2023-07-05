<?php
/**
 * Copyright (c) Enalean, 2016-Present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

class HudsonTestResult
{
    protected $hudson_test_result_url;
    protected $dom_job;
    /**
     * @var ClientInterface
     */
    private $http_client;
    /**
     * @var RequestFactoryInterface
     */
    private $request_factory;

    /**
     * Construct an Hudson job from a job URL
     */
    public function __construct(
        string $hudson_job_url,
        ClientInterface $http_client,
        RequestFactoryInterface $request_factory,
        ?SimpleXMLElement $dom_job = null,
    ) {
        $parsed_url = parse_url($hudson_job_url);

        if (! $parsed_url || ! array_key_exists('scheme', $parsed_url)) {
            throw new HudsonJobURLMalformedException(sprintf(dgettext('tuleap-hudson', 'Wrong Job URL: %1$s'), $hudson_job_url));
        }

        $this->hudson_test_result_url = $hudson_job_url . "/lastBuild/testReport/api/xml/";
        $this->http_client            = $http_client;
        $this->request_factory        = $request_factory;

        if ($dom_job !== null) {
            $this->dom_job = $dom_job;
        } else {
            $this->dom_job = $this->_getXMLObject($this->hudson_test_result_url);
        }
    }

    protected function _getXMLObject($hudson_test_result_url)
    {
        $response = $this->http_client->sendRequest(
            $this->request_factory->createRequest('GET', $hudson_test_result_url)
        );
        if ($response->getStatusCode() !== 200) {
            throw new HudsonJobURLFileNotFoundException(sprintf(dgettext('tuleap-hudson', 'File not found at URL: %1$s'), $hudson_test_result_url));
        }

        $xmlobj = simplexml_load_string($response->getBody()->getContents());
        if ($xmlobj !== false) {
            return $xmlobj;
        }
        throw new HudsonJobURLFileException(sprintf(dgettext('tuleap-hudson', 'Unable to read file at URL: %1$s'), $hudson_test_result_url));
    }

    public function getFailCount()
    {
        return (int) $this->dom_job->failCount;
    }

    public function getPassCount()
    {
        return (int) $this->dom_job->passCount;
    }

    public function getSkipCount()
    {
        return (int) $this->dom_job->skipCount;
    }

    public function getTotalCount()
    {
        return $this->getFailCount() + $this->getPassCount() + $this->getSkipCount();
    }
}
