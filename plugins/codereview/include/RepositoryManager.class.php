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

    /**
     * Class constructor
     *
     * @param codeReviewPlugin $plugin  Instance of the plugin
     * @param HTTPRequest      $request HTTP request
     *
     * @return Void
     */
    function __construct($plugin, $request) {
        $this->plugin  = $plugin;
        $project = ProjectManager::instance()->getProject($request->get('group_id'));
        // TODO: set project name as name for the repo
        $this->repoName       = $project->getUnixName();
        // TODO: set svn path as the path of project's repo
        $this->svnPath        = "http://svn.codex-cc.codex.cro.st.com/svnroot/codex-cc";
        // TODO: Choose the correct username to access svn repo
        $this->tuleapUser     = "user";
        // TODO: we may not use another alternative to authenticate through svn
        $this->tuleapPassword = "password";
        // TODO: Get this path, username & pass dinamically from plugin setup
        $this->rbPath         = "http://10.157.15.85";
        $this->rbUser         = "admin";
        $this->rbPassword     = "siteadmin";
    }

    /**
     * Check if the repository already exist in RB
     *
     * @return Boolean
     */
    public function isRepositoryAlreadyThere() {
        $exist = false;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/xml"));
        curl_setopt($ch, CURLOPT_USERPWD, $this->rbUser.":".$this->rbPassword); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $this->rbPath."/api/repositories/");
        $result = json_decode(curl_exec($ch), true);
        // TODO: handle errors
        $error = curl_error($ch);
        curl_close($ch);
        if ($result) {
            foreach($result['repositories'] as $repository) {
                if ($repository['path'] == $this->svnPath) {
                    $exist = true;
                    break;
                }
            }
        }
        return $exist;
    }

    /**
     * Add the reference to svn repository of the project
     *
     * @return Boolean
     */
    public function AddRepository() {
        // TODO: check if user have read permission on svn repo
        if (!$this->isRepositoryAlreadyThere()) {
            $data = array("name"     => $this->repoName,
                          "path"     => $this->svnPath,
                          "tool"     => "subversion",
                          "username" => $this->tuleapUser,
                          "password" => $this->tuleapPassword);
            $post = http_build_query($data, "", "&");
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/xml"));
            curl_setopt($ch, CURLOPT_USERPWD, $this->rbUser.":".$this->rbPassword); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_URL, $this->rbPath."/api/repositories/");
            curl_exec($ch);
            // TODO: handle errors
            $error = curl_error($ch);
            curl_close($ch);
        }
    }

}

?>