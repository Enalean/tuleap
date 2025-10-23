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

/**
 * Continuous integration for Git
 */
class Git_Ci // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    private $_dao; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

    /**
     * Get CI dao
     *
     * @return Git_Ci_Dao
     */
    public function getDao()
    {
        if (! isset($this->dao)) {
            $this->_dao = new Git_Ci_Dao();
        }
        return $this->_dao;
    }

    /**
     * Wrapper for unit tests
     *
     * @return ProjectManager
     */
    public function getProjectManager()
    {
        return ProjectManager::instance();
    }

    private function getEventManager()
    {
        return EventManager::instance();
    }

    /**
     * Retrieve git triggers
     *
     * @param Array $params Hook parameters
     *
     * @return Array
     */
    public function retrieveTriggers($params)
    {
        if (isset($params['group_id']) && ! empty($params['group_id'])) {
            $project = $this->getProjectManager()->getProject($params['group_id']);
            if ($project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
                $repositoryId = '';
                $used         = [];
                $checked      = '';
                if (isset($params['job_id']) && ! empty($params['job_id'])) {
                    $res = $this->getDao()->retrieveTrigger($params['job_id']);
                    if ($res && ! $res->isError() && $res->rowCount() == 1) {
                        $row          = $res->getRow();
                        $repositoryId = $row['repository_id'];
                        $checked      = 'checked="checked"';
                    }
                } else {
                    $res = $this->getDao()->retrieveTriggers($params['group_id']);
                    if ($res && ! $res->isError() && $res->rowCount() > 0) {
                        foreach ($res as $row) {
                            $used[$row['job_id']] = true;
                        }
                    }
                }

                $warning    = '';
                $intalled   = false;
                $parameters = [
                    'installed' => &$intalled,
                ];

                $this->getEventManager()->processEvent('display_hudson_addition_info', $parameters);
                if ($intalled) {
                    $warning = '<div class="tlp-alert-warning"> ' .
                        dgettext('tuleap-git', 'Starting Tuleap 8.14, we recommend you to use polling jobs. Please see <a href="/doc/en/user-guide/code-versioning/git.html?#jenkins-webhooks">Hudson Git Plugin</a>.') .
                        ' </div>';
                }

                $html_purifier = Codendi_HTMLPurifier::instance();

                $dao          = new GitDao();
                $repositories = $dao->getProjectRepositoryList($params['group_id'], false);
                $selectBox    = '<select class="tlp-select" id="hudson_use_plugin_git_trigger" name="hudson_use_plugin_git_trigger">';
                $selectBox   .= '<option>' . $GLOBALS['Language']->getText('global', 'none') . '</option>';
                foreach ($repositories as $repository) {
                    $nameSpace = '';
                    if (! empty($repository['repository_namespace'])) {
                        $nameSpace = $repository['repository_namespace'] . '/';
                    }
                    $selectBox .= '<option value="' . $html_purifier->purify((string) $repository['repository_id']) . '" ';
                    if ($repositoryId == $repository['repository_id']) {
                        $selectBox .= 'selected="selected"';
                    }
                    $selectBox .= '>' . $html_purifier->purify($nameSpace . $repository['repository_name']) . '</option>';
                }
                $selectBox .= '</select>';

                $addForm = '<label class="tlp-label tlp-checkbox continuous-integration-trigger-option">
                        <input name="hudson_use_plugin_git_trigger_checkbox" type="hidden" value="0" />
                        <input name="hudson_use_plugin_git_trigger_checkbox" class="continuous-integration-trigger-option-checkbox" type="checkbox" ' . $checked . ' value="1" />
                        Git
                    </label>
                    <blockquote class="continuous-integration-trigger-option-details">
                        ' . $warning . '
                        <div class="tlp-form-element">
                            <label class="tlp-label" for="hudson_use_plugin_git_trigger">' . dgettext('tuleap-git', 'repository') . '</label>
                            ' . $selectBox . '
                        </div>
                    </blockquote>';
                return ['service'       => GitPlugin::SERVICE_SHORTNAME,
                    'title'         => dgettext('tuleap-git', 'Git trigger'),
                    'used'          => $used,
                    'add_form'      => $addForm,
                    'edit_form'     => $addForm,
                ];
            }
        }
    }

    /**
     * Save a new trigger
     *
     * @param int $jobId Id of the CI job
     * @param int $repositoryId Id of the repository
     *
     * @return bool
     */
    public function saveTrigger($jobId, $repositoryId)
    {
        $dar = $this->getDao()->checkRepository($jobId, $repositoryId);
        if ($dar && ! $dar->isError() && $dar->rowCount() > 0) {
            return $this->getDao()->saveTrigger($jobId, $repositoryId);
        } else {
            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-git', 'Git repository does not belong to the project'));
            return false;
        }
    }

    /**
     * Delete trigger
     *
     * @param int $jobId Id of the CI job
     *
     * @return bool
     */
    public function deleteTrigger($jobId)
    {
        return $this->getDao()->deleteTrigger($jobId);
    }
}
