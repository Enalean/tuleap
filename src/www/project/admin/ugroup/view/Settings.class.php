<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Project_Admin_UGroup_View_Settings extends Project_Admin_UGroup_View {
    const IDENTIFIER = 'settings';

    public function getContent() {
        $content = '<p>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'upd_ug_name').'</p>
        <form method="post" name="form_create" action="/project/admin/ugroup.php?group_id='.$this->ugroup->getProjectId().'" onSubmit="return selIt();">
        <input type="hidden" name="func" value="do_update">
        <input type="hidden" name="group_id" value="'.$this->ugroup->getProjectId().'">
        <input type="hidden" name="ugroup_id" value="'.$this->ugroup->getId().'">
        <div class="control-group">
            <label class="control-label">'.$GLOBALS['Language']->getText('project_admin_editugroup', 'name').'</label>
            <div class="controls">
                <input size="40"
                       type="text"
                       name="ugroup_name"
                       value="'.$this->ugroup->getName().'"
                       required
                       autofocus/>
                <div class="help">'.$GLOBALS['Language']->getText('project_admin_editugroup', 'avoid_special_ch').'</div>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">'.$GLOBALS['Language']->getText('project_admin_editugroup', 'desc').'</label>
            <div class="controls">
                <textarea
                    name="ugroup_description"
                    wrap="virtual"
                    cols="40"
                    rows="3"">'.$this->ugroup->getDescription().'</textarea>
            </div>
        </div>
        <div class="control-group">
            <div class="controls">
                <input type="submit" value="'.$GLOBALS['Language']->getText('global', 'btn_update').'" />
            </div>
        </div>
        </form>';
        return $content;
    }

    public function getIdentifier() {
        return self::IDENTIFIER;
    }
}

?>
