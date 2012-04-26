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
 * codereview
 */
class RepositoryManager {

    public $plugin;

    /**
     * Class constructor
     *
     * @param codeReviewPlugin $plugin Instance of the plugin
     *
     * @return Void
     */
    function __construct($plugin) {
        $this->plugin  = $plugin;
    }

    /**
     * Add the reference to svn repository of the project
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Boolean
     */
    public function AddRepository($request) {
        // TODO: check if user have read permission on svn repo
        // TODO: set project name as name for the repo
        $repoName       = "ProjectName";
        // TODO: set svn path as the path of project's repo
        $svnPath        = "http://svn.codex-cc.codex.cro.st.com/svnroot/codex-cc";
        // TODO: Choose the correct username to access svn repo
        $tuleapUser     = "user";
        // TODO: we may not use another alternative to authenticate through svn
        $tuleapPassword = "password";
        // TODO: Get this path, username & pass dinamically from plugin setup
        $rbPath         = "10.157.15.85";
        $rbUser         = "admin";
        $rbPassword     = "siteadmin";
        $data           = array("name"     => $repoName,
                                "path"     => $svnPath,
                                "tool"     => "subversion",
                                "username" => $tuleapUser,
                                "password" => $tuleapPassword);
        $post           = http_build_query($data, "", "&");

        // test if the repository already exist in RB
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/xml"));
        curl_setopt($ch, CURLOPT_USERPWD, $rbUser.":".$rbPassword); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, "http://".$rbPath."/api/repositories/");
        $result = json_decode(curl_exec($ch), true);
        $exist = false;
        foreach($result['repositories'] as $repository) {
            if ($repository['path'] == $svnPath) {
                $exist = true;
                break;
            }
        }

        if (!$exist) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/xml"));
            curl_setopt($ch, CURLOPT_USERPWD, $rbUser.":".$rbPassword); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_URL, "http://".$rbPath."/api/repositories/");
            curl_exec($ch);
            // TODO: handle errors
            $error = curl_error($ch);
            curl_close($ch);
        }
    }

}

?>