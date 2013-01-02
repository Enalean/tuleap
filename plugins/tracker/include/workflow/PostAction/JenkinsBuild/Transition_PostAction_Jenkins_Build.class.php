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

require_once(dirname(__FILE__).'/../../../../include/JenkinsClient.class.php');

class Transition_PostAction_Jenkins_Build extends Transition_PostAction {

    /**
     *
     * @var String : host of the jenkins server
     */
    private $host;

    /**
     *
     * @var String job_name : name of the job to build
     */
    private $job_name;

    /**
     * Constructor
     *
     * @param Transition                   $transition The transition the post action belongs to
     * @param Integer                      $id         Id of the post action
     * @param String                       $host       host of the jenkins server
     * @param String                       $job_name   name of the job
     */
    public function __construct(Transition $transition, $id, $host, $job_name) {
        parent::__construct($transition, $id);
        $this->host     = $host;
        $this->job_name = $job_name;
    }

    public function getHost() {
        return $this->host;
    }

    public function getJobName() {
        return $this->job_name;
    }

    /**
     * Get the shortname of the post action
     *
     * @return string
     */
    public function getShortName() {
        return 'jenkins_build';
    }

    public function fetch() {

    }

    public function isDefined() {
        return true;
    }

    public function process(Codendi_Request $request) {

    }

    public static function getLabel() {
        return 'Launch a jenkins build';
    }

    public function after() {
        $jenkins_client = new JenkinsClient($this->host);
        return $jenkins_client->launchJobBuild($this->job_name);
    }
}

?>
