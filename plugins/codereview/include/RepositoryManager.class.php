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

require_once 'common/curl/TuleapCurl.class.php';

/**
 * Manager of RB repositories
 */
class RepositoryManager {

    public $plugin;
    public $repoName;
    public $tuleapUser;
    public $tuleapPassword;
    public $rbPath;
    public $rbUser;
    public $rbPassword;
    public $projectname;

    /**
     * Class constructor
     *
     * @param codeReviewPlugin $plugin  Instance of the plugin
     * @param HTTPRequest      $request HTTP request
     *
     * @return Void
     */
    function __construct($plugin, $request) {
        $this->plugin         = $plugin;
        $pluginInfo           = $this->plugin->getPluginInfo();
        $project              = ProjectManager::instance()->getProject($request->get('group_id'));
        $this->projectname    = $project->getPublicName();
        $this->repoName       = $project->getUnixName();
        // TODO: Decide whether to go on or not if project doesn't use svn
        $this->svnPath        = svn_utils_get_svn_path($project);
        $this->tuleapUser     = $pluginInfo->getPropertyValueForName('tuleap_user');
        // TODO: we may use another alternative to authenticate through svn
        $this->tuleapPassword = $pluginInfo->getPropertyValueForName('tuleap_pw');
        $this->rbPath         = $pluginInfo->getPropertyValueForName('reviewboard_site');
        $this->rbUser         = $pluginInfo->getPropertyValueForName('admin_user');
        $this->rbPassword     = $pluginInfo->getPropertyValueForName('admin_pwd');
    }

    /**
     * Check if the repository already exist in RB
     *
     * @return Boolean
     */
    public function isRepositoryAlreadyThere() {
        $exist  = false;
        $curl   = new TuleapCurl();
        $result = $curl->execute($this->rbPath."/api/repositories/", $this->rbUser, $this->rbPassword, false);
        //handle errors
        if ($result['return']) {
            foreach ($result['return']['repositories'] as $repository) {
                if ($repository['path'] == $this->svnPath) {
                    $exist = true;
                    break;
                }
            }
        }
        else{
            $msg = "This Repo does not exist.";
            $GLOBALS['Response']->addFeedBack('error', $msg);
            $this->controller->view = 'displayFirstFrame';
        }
        return $exist;
    }

    /**
     * Add the reference to svn repository of the project
     *
     * @return Boolean
     */
    public function addRepository() {
        //check if user have read permission on svn repo
        $user               = UserManager::instance()->getCurrentUser();
        $username           = $user->getUserName();
        $boolean            = svn_utils_check_access($username,$this->projectname,$this->svnpath);
        if ((!$this->isRepositoryAlreadyThere()) && ($boolean)) {
            $data = array("name"     => $this->repoName,
                          "path"     => $this->svnPath,
                          "tool"     => "subversion",
                          "username" => $this->tuleapUser,
                          "password" => $this->tuleapPassword);
            $curl   = new TuleapCurl();
            $result = $curl->execute($this->rbPath."/api/repositories/", $this->rbUser, $this->rbPassword, $data, true);
            //Handle error
            if ($result['stat']=="fail"){
                $msg = "The repo was not created.";
                $GLOBALS['Response']->addFeedBack('error', $msg);
                $this->controller->view = 'displayFirstFrame';
            }
        }
    }

}

?>