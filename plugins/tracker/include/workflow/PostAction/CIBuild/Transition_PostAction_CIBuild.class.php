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

require_once 'common/Jenkins/Client.class.php';

class Transition_PostAction_CIBuild extends Transition_PostAction {

    /**
     *
     * @var String job_name : name of the job to build
     */
    private $job_url;

    /**
     * Constructor
     *
     * @param Transition                   $transition The transition the post action belongs to
     * @param Integer                      $id         Id of the post action
     * @param String                       $host       host of the jenkins server
     * @param String                       $job_url   name of the job
     */
    public function __construct(Transition $transition, $id, $job_url) {
        parent::__construct($transition, $id);
        $this->job_url = $job_url;
    }

    
    public function getJobUrl() {
        return $this->job_url;
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
        $html = '';
        $text_field = '<input type="text" name="workflow_postaction_launch_job['.$this->id.']" value="'.$this->getJobUrl().'" size="50" maxsize="255"/>';
        $html .= $GLOBALS['Language']->getText('workflow_admin', 'launch_job', array($text_field));
        return $html;
    }

    public function isDefined() {
        return true;
    }

    public function process(Codendi_Request $request) {
        if ($request->getInArray('remove_postaction', $this->id)) {
            //$this->getDao()->deletePostAction($this->id);
        } else {
            $value    = $request->getInArray('workflow_postaction_launch_job', $this->id);
            // Update if something changed
            if ($value != $this->job_url) {
                $this->getDao()->updatePostAction($this->id, $value);
            }
        }
    }

    public static function getLabel() {
        return 'Launch a jenkins build';
    }

    public function after() {
        $jenkins_client = new Jenkins_Client($this->host);
        return $jenkins_client->launchJobBuild($this->job_name);
    }
    
    public function getDao() {
        return new Transition_PostAction_CIBuildDao();
    }
}

?>
