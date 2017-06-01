<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All rights reserved
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


require_once('HudsonJobWidget.class.php');
require_once('common/user/UserManager.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('PluginHudsonJobDao.class.php');
require_once('HudsonJob.class.php');
require_once('HudsonTestResult.class.php');

class hudson_Widget_JobTestResults extends HudsonJobWidget {

    var $test_result;

    /**
     * Constructor
     *
     * @param String           $owner_type The owner type
     * @param Int              $owner_id   The owner id
     * @param HudsonJobFactory $factory    The HudsonJob factory
     *
     * @return void
     */
    function __construct($owner_type, $owner_id, HudsonJobFactory $factory) {
        $request =& HTTPRequest::instance();
        if ($owner_type == WidgetLayoutManager::OWNER_TYPE_USER) {
            $this->widget_id = 'plugin_hudson_my_jobtestresults';
            $this->group_id = $owner_id;
        } else {
            $this->widget_id = 'plugin_hudson_project_jobtestresults';
            $this->group_id = $request->get('group_id');
        }
        parent::__construct($this->widget_id, $factory);

        $this->setOwner($owner_id, $owner_type);
    }

    function getTitle() {
        $title = '';
        if ($this->job && $this->test_result) {
            $title .= $GLOBALS['Language']->getText('plugin_hudson', 'project_job_testresults_widget_title', array($this->job->getName(), $this->test_result->getPassCount(), $this->test_result->getTotalCount()));
        } elseif ($this->job && ! $this->test_result) {
            $title .= $GLOBALS['Language']->getText('plugin_hudson', 'project_job_testresults_projectname', array($this->job->getName()));
        } else {
            $title .= $GLOBALS['Language']->getText('plugin_hudson', 'project_job_testresults');
        }
        $purifier = Codendi_HTMLPurifier::instance();
        return $purifier->purify($title);
    }

    function getDescription() {
        return $GLOBALS['Language']->getText('plugin_hudson', 'widget_description_testresults');
    }

    function loadContent($id)
    {
        $this->content_id = $id;
    }

    protected function initContent()
    {
        $job_id = $this->getJobIdFromWidgetConfiguration();
        if ($job_id) {
            $this->job_id = $job_id;

            $jobs = $this->getAvailableJobs();

            if (array_key_exists($this->job_id, $jobs)) {
                try {
                    $used_job          = $jobs[$this->job_id];
                    $this->job_url     = $used_job->getUrl();
                    $this->job         = $used_job;
                    $http_client       = new Http_Client();
                    $this->test_result = new HudsonTestResult($this->job_url, $http_client);
                } catch (Exception $e) {
                    $this->test_result = null;
                }

            } else {
                $this->job = null;
                $this->test_result = null;
            }

        }
    }

    function getContent() {
        $this->initContent();

        $html = '';
        if ($this->job != null && $this->test_result != null) {

            $job = $this->job;
            $test_result = $this->test_result;

            $html .= '<div style="padding: 20px;">';
            $html .= ' <a href="/plugins/hudson/?action=view_last_test_result&group_id='.$this->group_id.'&job_id='.$this->job_id.'">'.$test_result->getTestResultPieChart().'</a>';
            $html .= '</div>';

        } else {
            if ($this->job != null) {
                $html .= $GLOBALS['Language']->getText('plugin_hudson', 'widget_tests_not_found');
            } else {
                $html .= $GLOBALS['Language']->getText('plugin_hudson', 'widget_job_not_found');
            }
        }

        return $html;
    }
}

?>
