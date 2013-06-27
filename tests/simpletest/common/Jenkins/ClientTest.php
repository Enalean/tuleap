<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/Jenkins/Client.class.php';

class Jenkins_ClientTest extends TuleapTestCase {

    private $http_client;
    private $jenkins_client;
    
    public function setUp() {
        parent::setUp();
        $this->http_client = mock('Http_Client');
        $this->jenkins_client = new Jenkins_Client($this->http_client);
    }

    public function testLaunchJobBuildThrowsAnExceptionOnFailedRequest() {
        $job_url = 'http://some.url.com/my_job';
        stub($this->http_client)->doRequest()->throws(new Http_ClientException());

        $this->expectException('Jenkins_ClientUnableToLaunchBuildException');
        $this->jenkins_client->launchJobBuild($job_url);
    }

    public function testLaunchJobSetsCorrectOptions() {
        $job_url = 'http://degaine:8080/job/dylanJob';
        $build_parameters = array(
            'my_param' => 'mickey mooouse',
        );

        $json_params = '{"parameter":[{"name":"my_param","value":"mickey mooouse"}]}';
        $expected_url_params = urlencode($json_params);

        $expected_options = array(
            CURLOPT_URL             => $job_url . '/build',
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => 'json='. $expected_url_params,
        );

        stub($this->http_client)->doRequest()->once();
        stub($this->http_client)->addOptions($expected_options)->once();

        $this->jenkins_client->launchJobBuild($job_url, $build_parameters);
    }

    public function itPassTokenAsParameter() {
        $job_url = 'http://degaine:8080/job/dylanJob';

        $expected_options = array(
            CURLOPT_URL             => $job_url . '/build?token=thou+shall+not+pass',
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPGET         => true,
        );

        stub($this->http_client)->addOptions($expected_options)->once();

        $this->jenkins_client->setToken('thou shall not pass');
        $this->jenkins_client->launchJobBuild($job_url);
    }
}
?>
