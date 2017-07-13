<?php
/**
 * Copyright (c) Enalean, 2013-2017. All Rights Reserved.
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
        $purifier = Codendi_HTMLPurifier::instance();

        $content = '<h2>'. $GLOBALS['Language']->getText('project_admin_editugroup','settings_title') .'</h2>' .
        '<p>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'upd_ug_name').'</p>
        <form method="post" name="form_create" action="/project/admin/ugroup.php?group_id='.urlencode($this->ugroup->getProjectId()).'">
        <input type="hidden" name="func" value="do_update">
        <input type="hidden" name="group_id" value="'.$purifier->purify($this->ugroup->getProjectId()).'">
        <input type="hidden" name="ugroup_id" value="'.$purifier->purify($this->ugroup->getId()).'">
        <p>
            <label for="ugroup_name"><strong>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'name').'</strong></label>
            <br />
            <input size="40"
                   type="text"
                   name="ugroup_name"
                   value="'.$purifier->purify($this->ugroup->getName()).'"
                   required
                   autofocus/>
            <br />
            <span class="help">'.$GLOBALS['Language']->getText('project_admin_editugroup', 'avoid_special_ch').'</span>
        </p>
        <p>
            <label for="ugroup_description"><strong>'.$GLOBALS['Language']->getText('project_admin_editugroup', 'desc').'</strong></label>
            <br />
            <textarea
                name="ugroup_description"
                wrap="virtual"
                cols="40"
                rows="3"">'.$purifier->purify($this->ugroup->getDescription()).'</textarea>
        </p>
        <p>
            <input type="submit" value="'.$GLOBALS['Language']->getText('global', 'btn_update').'" />
        </p>
        </form>';
        return $content;
    }

    public function getIdentifier() {
        return self::IDENTIFIER;
    }
}

?>
