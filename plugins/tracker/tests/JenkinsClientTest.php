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

    public function testLaunchJobBuildThrowsAnExceptionOnFailedRequest() 
    {
        $job_url = 'http://some.url.com/my_job';
        $mocked_methods = array('execute', 'getErrorCode', 'getLastError');
        $jenkins_client = partial_mock('JenkinsClient', $mocked_methods);
        
        stub($jenkins_client)->execute()->once()->returns(false);
        stub($jenkins_client)->getErrorCode()->once()->returns(666);
        
        $this->expectException('JenkinsClientUnableToLaunchBuildException');
        $jenkins_client->launchJobBuild($job_url); 
    }
    
    public function testLaunchJobBuildDoesNotThrowAnExceptionOnValidRequest() 
    {
        $job_url = 'http://some.url.com/my_job';
        $mocked_methods = array('execute', 'getErrorCode', 'getLastError');
        $jenkins_client = partial_mock('JenkinsClient', $mocked_methods);
        
        stub($jenkins_client)->execute()->once()->returns(true);
        stub($jenkins_client)->getErrorCode()->once()->returns(null);

        $jenkins_client->launchJobBuild($job_url); 
     }
     
     public function testLaunchJobSetsCorrectOptions() {
         $job_url = 'http://some.url.com/my_job';
         $mocked_methods = array('execute', 'getErrorCode', 'getLastError');
         $jenkins_client = partial_mock('JenkinsClient', $mocked_methods);
         
         $jenkins_client->launchJobBuild($job_url);
         
         $this->assertEqual($job_url . '/build', $jenkins_client->getOption(CURLOPT_URL));
         $this->assertEqual(true, $jenkins_client->getOption(CURLOPT_HTTPGET));
         $this->assertEqual(false, $jenkins_client->getOption(CURLOPT_SSL_VERIFYPEER));
     }

}
?>
