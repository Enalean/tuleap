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

    public function testLaunchJobBuildThrowsAnExceptionOnFailedRequest() {
        $job_url = 'http://some.url.com/my_job';
        $http_client = mock('Http_Client');
        stub($http_client)->doRequest()->throws(new Http_ClientException());

        $jenkins_client = new Jenkins_Client($http_client);
        $this->expectException('Jenkins_ClientUnableToLaunchBuildException');
        $jenkins_client->launchJobBuild($job_url);
    }

    public function testLaunchJobSetsCorrectOptions() {
        $job_url = 'http://some.url.com/my_job';
        $build_parameters = array(
            'my_param' => 'mickey mooouse',
        );
        
        $http_client = mock('Http_Client');
        stub($http_client)->doRequest()->once();
        
        $json_params = '{"parameter":[{"name":"my_param","value":"mickey mooouse"}]}';
        $expected_url_params = urlencode($json_params);

        $expected_options = array(
            CURLOPT_HTTPGET         => true,
            CURLOPT_URL             => $job_url . '/build?json=' . $expected_url_params,
            CURLOPT_SSL_VERIFYPEER  => false,
        );
        stub($http_client)->addOptions($expected_options)->once();

        $jenkins_client = new Jenkins_Client($http_client);
        $jenkins_client->launchJobBuild($job_url, $build_parameters);
    }
}
?>
