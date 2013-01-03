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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once 'HttpCurlClient.class.php';
require_once 'JenkinsClientUnableToLaunchBuildException.class.php';

/**
 * A class to be able to work with the jenkins server
 */
class JenkinsClient extends HttpCurlClient {
    
    /**
     * 
     * @param string $job_name
     * @param array $options curl options
     * @throws Tracker_Exception
     */
    public function launchJobBuild($job_url) {
        $url = $job_url . '/build';
        $options = array(
            CURLOPT_HTTPGET         => true,
            CURLOPT_URL             => $url,
            CURLOPT_SSL_VERIFYPEER  => false,
        );
        $this->addOptions($options);
        
        try {
            $this->doRequest();
        } catch (HttpCurlClientException $e) {
            throw new JenkinsClientUnableToLaunchBuildException('Job: ' . $job_url . ' ;Message: ' . $e->getMessage());
        }
    }
}

?>
