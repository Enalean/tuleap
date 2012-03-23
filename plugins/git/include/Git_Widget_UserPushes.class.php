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

require_once 'Git_LogDao.class.php';

/**
 * Widget displaying last git pushes for the user
 */
class Git_Widget_UserPushes extends Widget {

    var $repositoryId;
    var $offset;

    /**
     * Constructor of the class
     *
     * @return Void
     */
    public function __construct() {
        $this->Widget('plugin_git_user_pushes');
        $this->repositoryId = user_get_preference('plugin_git_user_pushes_repo_id');
        $this->offset       = user_get_preference('plugin_git_user_pushes_offset');
    }

    /**
     * Get the title of the widget.
     *
     * @return string
     */
    public function getTitle() {
        return 'My last Git pushes';
    }

    /**
     * Compute the content of the widget
     *
     * @return string html
     */
    public function getContent() {
        $content = '';
        $dao     = new Git_LogDao();
        $um      = UserManager::instance();
        $user    = $um->getCurrentUser();
        $dar     = $dao->getLastPushesByUser($user->getId(), $this->repositoryId, $this->offset);
        foreach($dar as $row) {
            $content .= $row['group_name'].' '.$row['repository_name']."<br />";
        }
        return $content;
    }

    /**
     * The category of the widget is scm
     *
     * @return string
     */
    function getCategory() {
        return 'scm';
    }

    /**
     * Display widget's description
     *
     * @return String
     */
    function getDescription() {
        return 'Display last pushes performed by the user';
    }

    function updatePreferences(&$request) {
        $request->valid(new Valid_String('cancel'));
        $vRepo = new Valid_UInt('plugin_git_user_pushes_repo_id');
        $vOffset = new Valid_UInt('plugin_git_user_pushes_offset');
        $vRepo->required();
        if (!$request->exist('cancel')) {
            if ($request->valid($vRepo)) {
                $this->repositoryId = $request->get('plugin_git_user_pushes_repo_id');
                user_set_preference('plugin_git_user_pushes_repo_id', $this->repositoryId);
            }
            if ($request->valid($vOffset)) {
                $this->repositoryId = $request->get('plugin_git_user_pushes_offset');
                user_set_preference('plugin_git_user_pushes_repo_id', $this->repositoryId);
            }
        }
        return true;
    }

    function hasPreferences() {
        return true;
    }

    function getPreferences() {
        return "<table>
                <tr>
                <td>Repository id</td><td><input name='plugin_git_user_pushes_repo_id'value='".$this->repositoryId."'/></td>
                </tr>
                <tr>
                <td>Offset</td><td><input name='plugin_git_user_pushes_offset'value='".$this->offset."'/></td>
                </tr>
                </table>";
        
    }

}

?>
