<?php
/**
 * Copyright (c) Enalean, 2016. All rights reserved
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


class HudsonTestResult {

    protected $hudson_test_result_url;
    protected $dom_job;
    /**
     * @var Http_Client
     */
    private $http_client;

    /**
     * Construct an Hudson job from a job URL
     */
    public function __construct($hudson_job_url, Http_Client $http_client)
    {
        $parsed_url = parse_url($hudson_job_url);

        if ( ! $parsed_url || ! array_key_exists('scheme', $parsed_url) ) {
            throw new HudsonJobURLMalformedException($GLOBALS['Language']->getText('plugin_hudson','wrong_job_url', array($hudson_job_url)));
        }

        $this->hudson_test_result_url = $hudson_job_url . "/lastBuild/testReport/api/xml/";
        $this->http_client            = $http_client;

        $this->dom_job = $this->_getXMLObject($this->hudson_test_result_url);
    }

    protected function _getXMLObject($hudson_test_result_url)
    {
        $this->http_client->setOption(CURLOPT_URL, $hudson_test_result_url);
        $this->http_client->doRequest();

        $xmlstr = $this->http_client->getLastResponse();
        if ($xmlstr !== false) {
            $xmlobj = simplexml_load_string($xmlstr);
            if ($xmlobj !== false) {
                return $xmlobj;
            } else {
                throw new HudsonJobURLFileException($GLOBALS['Language']->getText('plugin_hudson','job_url_file_error', array($hudson_test_result_url)));
            }
        } else {
            throw new HudsonJobURLFileNotFoundException($GLOBALS['Language']->getText('plugin_hudson','job_url_file_not_found', array($hudson_test_result_url)));
        }
    }

    public function getFailCount() {
        return (int) $this->dom_job->failCount;
    }
    public function getPassCount() {
        return (int) $this->dom_job->passCount;
    }
    public function getSkipCount() {
        return (int) $this->dom_job->skipCount;
    }
    public function getTotalCount() {
        return $this->getFailCount() + $this->getPassCount() + $this->getSkipCount();
    }
}
