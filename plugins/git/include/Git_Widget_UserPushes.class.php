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

    var $offset   = 5;
    var $pastDays = 30;

    /**
     * Constructor of the class
     *
     * @return Void
     */
    public function __construct() {
        $this->Widget('plugin_git_user_pushes');
        $this->offset   = user_get_preference('plugin_git_user_pushes_offset');
        if (empty($this->offset)) {
            $this->offset = 5;
        }
        $this->pastDays = user_get_preference('plugin_git_user_pushes_past_days');
        if (empty($this->pastDays)) {
            $this->pastDays = 30;
        }
    }

    /**
     * Get the title of the widget.
     *
     * @return string
     */
    public function getTitle() {
        return $GLOBALS['Language']->getText('plugin_git', 'widget_user_pushes_title');
    }

    /**
     * Compute the content of the widget
     *
     * @return string html
     */
    public function getContent() {
        $dao     = new Git_LogDao();
        $um      = UserManager::instance();
        $user    = $um->getCurrentUser();
        $date    = time() - ($this->pastDays * 24 * 60 * 60);
        $result  = $dao->getLastPushesRepositories($user->getId(), $date);
        $content = '';
        $project = '';
        if ($result && !$result->isError() && $result->rowCount() > 0) {
            foreach ($result as $entry) {
                $dar = $dao->getLastPushesByUser($user->getId(), $entry['repository_id'], $this->offset, $date);
                if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
                    if ($project != $entry['group_name']) {
                        if (!empty($project)) {
                            $content .= '</fieldset>';
                        }
                        $project = $entry['group_name'];
                        $content .= '<fieldset><legend id="plugin_git_user_pushes_widget_project_'.$project.'" class="'.Toggler::getClassname('plugin_git_user_pushes_widget_project_'.$project).'"><b>'.$project.'</b></legend>';
                    }
                    $content .= '<fieldset><legend id="plugin_git_user_pushes_widget_repo_'.$entry['repository_name'].'" class="'.Toggler::getClassname('plugin_git_user_pushes_widget_project_'.$project).'">'.$entry['repository_name'].'</legend>'.html_build_list_table_top(array($GLOBALS['Language']->getText('plugin_git', 'tree_view_date'), $GLOBALS['Language']->getText('plugin_git', 'tree_view_commits')));
                    $i       = 0;
                    $hp      = Codendi_HTMLPurifier::instance();
                    foreach ($dar as $row) {
                        $content .= '<tr class="'.html_get_alt_row_color(++$i).'">
                                         <td>'.html_time_ago($hp->purify($row['push_date'])).'</td>
                                         <td>'.$hp->purify($row['commits_number']).'</td>
                                     </tr>';
                    }
                    $content .= "</table></fieldset>";
                } else {
                    $content .= $GLOBALS['Language']->getText('plugin_git', 'widget_user_pushes_no_content');
                }
            }
        } else {
            $content = $GLOBALS['Language']->getText('plugin_git', 'widget_user_pushes_no_content');
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
        return $GLOBALS['Language']->getText('plugin_git', 'widget_user_pushes_description');
    }

    /**
     * Update preferences
     *
     * @param Array $request HTTP request
     *
     * @return Boolean
     */
    function updatePreferences($request) {
        $request->valid(new Valid_String('cancel'));
        $vOffset = new Valid_UInt('plugin_git_user_pushes_offset');
        $vOffset->required();
        $vDays   = new Valid_UInt('plugin_git_user_pushes_past_days');
        $vDays->required();
        if (!$request->exist('cancel')) {
            if ($request->valid($vOffset)) {
                $this->offset = $request->get('plugin_git_user_pushes_offset');
            } else {
                $this->offset = 5;
            }
            if ($request->valid($vDays)) {
                $this->pastDays = $request->get('plugin_git_user_pushes_past_days');
            } else {
                $this->pastDays = 30;
            }
            user_set_preference('plugin_git_user_pushes_offset', $this->offset);
            user_set_preference('plugin_git_user_pushes_past_days', $this->pastDays);
        }
        return true;
    }

    /**
     * Widget has preferences
     *
     * @return Boolean
     */
    function hasPreferences() {
        return true;
    }

    /**
     * Display preferences form
     *
     * @return String
     */
    function getPreferences() {
        return "<table>
                    <tr>
                        <td>".$GLOBALS['Language']->getText('plugin_git', 'widget_user_pushes_offset')."</td>
                        <td><input name='plugin_git_user_pushes_offset' value='".$this->offset."'/></td>
                    </tr>
                    <tr>
                        <td>".$GLOBALS['Language']->getText('plugin_git', 'widget_user_pushes_past_days')."</td>
                        <td><input name='plugin_git_user_pushes_past_days' value='".$this->pastDays."'/></td>
                    </tr>
                </table>";
        
    }

}

?>
