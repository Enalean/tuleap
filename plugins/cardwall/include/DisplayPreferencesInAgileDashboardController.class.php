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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */


require_once 'common/mvc2/PluginController.class.php';

class Cardwall_DisplayPreferencesInAgileDashboardController extends MVC2_PluginController {

    public function __construct($request) {
        parent::__construct('agiledashboard', $request);
    }

    public function toggleUserDisplay() {

        $tracker_id = $this->request->get('tracker_id');
        $pref_name  = Cardwall_DisplayPreferences::ASSIGNED_TO_USERNAME_PREFERENCE_NAME.$tracker_id;
        $user       = $this->getCurrentUser();
        $current_preference = $user->getPreference($pref_name);

        if (! $current_preference) {
            $user->setPreference($pref_name, Cardwall_DisplayPreferences::DISPLAY_AVATARS);
        } else {
            $this->switchPreference($user, $current_preference, $pref_name);
        }

        $this->redirect(array(
            'group_id'    => $this->request->getValidated('group_id', 'int'),
            'planning_id' => $this->request->get('planning_id'),
            'action'      => 'show',
            'aid'         => $this->request->get('aid'),
            'pane'        => 'cardwall'
        ));
    }

    private function switchPreference($user, $current_preference, $pref_name) {
        $pref_value = Cardwall_DisplayPreferences::DISPLAY_AVATARS;
        if ($current_preference == $pref_value) {
            $pref_value = Cardwall_DisplayPreferences::DISPLAY_USERNAMES;
        }

        $user->setPreference($pref_name, $pref_value);
    }
}

?>
