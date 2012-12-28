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

/**
 * A class to be able to work with the jenkins server
 */
class JenkinsClient {

    /**
     * @var String : the server host
     */
    private $host;

    public function __construct($host) {
        $this->host = $host;
    }

    private function BuildURLJobBuild($job_name) {
        if ($job_name === null) {
            return false;
        }
        return "http://$this->host/job/$job_name/build";
    }

    public function lunchJobBuild($job_name) {
        $url = $this->BuildURLJobBuild($job_name);
        return $url;
    }

}

?>
