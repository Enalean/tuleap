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

require_once(dirname(__FILE__).'/../include/JenkinsClient.class.php');

class JenkinsClientTest extends TuleapTestCase {

    public function testLaunchJobBuildThrowsAnExceptionOnFailedRequest() {
        $job_url = 'http://some.url.com/my_job';
        $http_client = mock('HttpCurlClient');
        stub($http_client)->doRequest()->throws(new HttpCurlClientException());

        $jenkins_client = new JenkinsClient($http_client);
        $this->expectException('JenkinsClientUnableToLaunchBuildException');
        $jenkins_client->launchJobBuild($job_url);
    }

    public function testLaunchJobSetsCorrectOptions() {
        $job_url = 'http://some.url.com/my_job';

        $http_client = mock('HttpCurlClient');
        stub($http_client)->doRequest()->once();

        $expected_options = array(
            CURLOPT_HTTPGET         => true,
            CURLOPT_URL             => $job_url . '/build',
            CURLOPT_SSL_VERIFYPEER  => false,
        );
        stub($http_client)->addOptions($expected_options)->once();

        $jenkins_client = new JenkinsClient($http_client);
        $jenkins_client->launchJobBuild($job_url);
    }
}
?>
