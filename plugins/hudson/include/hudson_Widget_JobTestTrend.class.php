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


class hudson_Widget_JobTestTrend extends HudsonJobWidget {
    private $test_result;

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
        $request = HTTPRequest::instance();
        if ($owner_type == WidgetLayoutManager::OWNER_TYPE_USER) {
            $this->widget_id = 'plugin_hudson_my_jobtesttrend';
            $this->group_id = $owner_id;
        } else {
            $this->widget_id = 'plugin_hudson_project_jobtesttrend';
            $this->group_id = $request->get('group_id');
        }
        parent::__construct($this->widget_id, $factory);

        $this->setOwner($owner_id, $owner_type);
    }

    function getTitle() {
        $title = '';
        if ($this->job) {
            $title .= $GLOBALS['Language']->getText('plugin_hudson', 'project_job_testtrend', array($this->job->getName()));
        } else {
             $title .= $GLOBALS['Language']->getText('plugin_hudson', 'project_job_testtrend');
        }
        $purifier = Codendi_HTMLPurifier::instance();
        return $purifier->purify($title);
    }

    function getDescription() {
        return $GLOBALS['Language']->getText('plugin_hudson', 'widget_description_testtrend');
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

        $purifier = Codendi_HTMLPurifier::instance();
        $html     = '';
        if ($this->job != null && $this->test_result != null) {

            $job = $this->job;

            $html .= '<div style="padding: 20px;">';
            $html .= '<a href="/plugins/hudson/?action=view_test_trend&group_id='.urlencode($this->group_id).'&job_id='.urlencode($this->job_id).'">';
            $html .= '<img src="'.$purifier->purify($job->getUrl()).'/test/trend?width=320&height=240" alt="'.$purifier->purify($GLOBALS['Language']->getText('plugin_hudson', 'project_job_testtrend', array($this->job->getName()))).'" title="'.$purifier->purify($GLOBALS['Language']->getText('plugin_hudson', 'project_job_testtrend', array($this->job->getName()))).'" />';
            $html .= '</a>';
            $html .= '</div>';

        } else {
            if ($this->job != null) {
                $html .= $purifier->purify($GLOBALS['Language']->getText('plugin_hudson', 'widget_tests_not_found'));
            } else {
                $html .= $purifier->purify($GLOBALS['Language']->getText('plugin_hudson', 'widget_job_not_found'));
            }
        }

        return $html;
    }
}
