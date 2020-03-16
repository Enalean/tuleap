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


class Cardwall_UserPreferences_UserPreferencesController extends MVC2_PluginController
{

    public function __construct($request)
    {
        parent::__construct('agiledashboard', $request);
    }

    public function toggleUserDisplay()
    {
        $this->getCurrentUser()->togglePreference(
            Cardwall_UserPreferences_UserPreferencesDisplayUser::ASSIGNED_TO_USERNAME_PREFERENCE_NAME . $this->request->get('tracker_id'),
            Cardwall_UserPreferences_UserPreferencesDisplayUser::DISPLAY_AVATARS,
            Cardwall_UserPreferences_UserPreferencesDisplayUser::DISPLAY_USERNAMES
        );

        $this->redirect(array(
            'group_id'    => $this->request->getValidated('group_id', 'int'),
            'planning_id' => $this->request->get('planning_id'),
            'action'      => 'show',
            'aid'         => $this->request->get('aid'),
            'pane'        => 'cardwall'
        ));
    }

    public function toggleAutostack()
    {
        $this->getCurrentUser()->togglePreference(
            $this->request->get('name'),
            Cardwall_UserPreferences_UserPreferencesAutostack::STACK,
            Cardwall_UserPreferences_UserPreferencesAutostack::DONT_STACK
        );
    }
}
