<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

require_once('Git_CiDao.class.php');
require_once('GitDao.class.php');

/**
 * Continuous integration for Git
 */
class Git_Ci {

    private $_dao;

    /**
     * Get CI dao
     *
     * @return Git_CiDao
     */
    function getDao() {
        if (!isset($this->dao)) {
            $this->_dao = new Git_CiDao();
        }
        return $this->_dao;
    }

    /**
     * Wrapper for unit tests
     *
     * @return ProjectManager
     */
    function getProjectManager() {
        return ProjectManager::instance();
    }

    /**
     * Retrieve git triggers
     *
     * @param Array $params Hook parameters
     *
     * @return Array
     */
    function retrieveTriggers($params) {
        if (isset($params['group_id']) && !empty($params['group_id'])) {
            $project = $this->getProjectManager()->getProject($params['group_id']);
            if ($project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
                $repositoryId = '';
                $used         = array();
                $checked      = '';
                if (isset($params['job_id']) && !empty($params['job_id'])) {
                    $res = $this->getDao()->retrieveTrigger($params['job_id']);
                    if ($res && !$res->isError() && $res->rowCount() == 1) {
                        $row          = $res->getRow();
                        $repositoryId = $row['repository_id'];
                        $checked      = 'checked="checked"';
                    }
                } else {
                    $res = $this->getDao()->retrieveTriggers($params['group_id']);
                    if ($res && !$res->isError() && $res->rowCount() > 0) {
                        foreach ($res as $row) {
                            $used[$row['job_id']] = true;
                        }
                    }
                }
                $dao          = new GitDao();
                $repositories = $dao->getProjectRepositoryList($params['group_id'], false, false);
                $selectBox    = '<select id="hudson_use_plugin_git_trigger" name="hudson_use_plugin_git_trigger">';
                $selectBox    .= '<option>'.$GLOBALS['Language']->getText('global', 'none').'</option>';
                foreach ($repositories as $repository) {
                    $nameSpace = '';
                    if (!empty($repository['repository_namespace'])) {
                        $nameSpace = $repository['repository_namespace']."/";
                    }
                    $selectBox .= '<option value="'.$repository['repository_id'].'" ';
                    if ($repositoryId == $repository['repository_id']) {
                        $selectBox .= 'selected="selected"';
                    }
                    $selectBox .= '>'.$nameSpace.$repository['repository_name'].'</option>';
                }
                $selectBox .= '</select>';

                $form  = '<div id="hudson_use_plugin_git_trigger_checkbox">
                                 <label class="checkbox">
                                    <input onclick="toggle_checkbox(this)" onload="toggle_checkbox(this)" type="checkbox" '.$checked.' /> Git
                                </label>
                                <blockquote id="hudson_use_plugin_git_trigger_form">'.$GLOBALS['Language']->getText('plugin_git', 'ci_repo_id').': '.$selectBox.'</blockquote>
                             </div>
                             <script>
                                 function toggle_checkbox(checkbox) {
                                     var use_git = checkbox.checked;
                                     $(\'hudson_use_plugin_git_trigger_checkbox\').down(\'select\').disabled = !use_git;
                                 }
                             </script>';
                return array('service'       => GitPlugin::SERVICE_SHORTNAME,
                             'title'         => $GLOBALS['Language']->getText('plugin_git', 'ci_trigger'),
                             'used'          => $used,
                             'add_form'      => $form,
                             'edit_form'     => $form);
            }
        }
    }

    /**
     * Save a new trigger
     *
     * @param Integer $jobId        Id of the CI job
     * @param Integer $repositoryId Id of the repository
     *
     * @return Boolean
     */
    function saveTrigger($jobId, $repositoryId) {
        $dar = $this->getDao()->checkRepository($jobId, $repositoryId);
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            return $this->getDao()->saveTrigger($jobId, $repositoryId);
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_git', 'ci_repository_not_in_project'));
            return false;
        }
    }

    /**
     * Delete trigger
     *
     * @param Integer $jobId Id of the CI job
     *
     * @return Boolean
     */
    function deleteTrigger($jobId) {
        return $this->getDao()->deleteTrigger($jobId);
    }

    /**
     * Trigger CI build
     *
     * @param Integer $repositoryId Id of the repository where a push occured
     *
     * @return Void
     */
    function triggerCiBuild($repositoryId) {
        $res = $this->getDao()->retrieveTriggersPathByRepository($repositoryId);
        if ($res && !$res->isError() && $res->rowCount() > 0) {
            foreach ($res as $row) {
                $token = '';
                if (!empty($row['token'])) {
                    $token = '?token='.$row['token'];
                }
                $url = $row['job_url'].'/build';
                $context = null;
                $ch  = curl_init();
                curl_setopt($ch, CURLOPT_FAILONERROR, true);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_exec($ch);
                curl_close($ch);
            }
        }
    }

}

?>