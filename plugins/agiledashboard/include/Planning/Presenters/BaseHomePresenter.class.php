<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Planning_Presenter_BaseHomePresenter
{

    /** @var int */
    public $group_id;

    /** @var bool */
    public $is_user_admin;

    /**
     * @var bool
     */
    public $is_mono_milestone_enabled;

    public function __construct(
        $group_id,
        $is_user_admin,
        $is_mono_milestone_enabled
    ) {
        $this->group_id                  = $group_id;
        $this->is_user_admin             = $is_user_admin;
        $this->is_mono_milestone_enabled = $is_mono_milestone_enabled;
    }

    public function nothing_set_up()
    {
        if (! $this->is_user_admin) {
            return $GLOBALS['Language']->getText('plugin_agiledashboard', 'nothing_set_up_generic');
        }

        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'nothing_set_up_admin');
    }

    public function nothing_set_up_admin_description()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'nothing_set_up_admin_description');
    }

    public function come_back_later()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'nothing_set_up_come_back');
    }

    public function start_kanban()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'start_kanban');
    }

    public function start_scrum()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'start_scrum');
    }

    public function activate_scrum_v2()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'activate_scrum_v2');
    }

    public function create_scrum_url()
    {
        $params = $this->getBaseParameters();
        $params['activate-scrum']    = 1;
        $params['scrum-title-admin'] = 'Scrum';

        return '?' . http_build_query($params);
    }

    public function create_kanban_url()
    {
        $params = $this->getBaseParameters();
        $params['activate-kanban']    = 1;
        $params['kanban-title-admin'] = 'Kanban';

        return '?' . http_build_query($params);
    }

    private function getBaseParameters()
    {
        $token      = new CSRFSynchronizerToken('/plugins/agiledashboard/?action=admin');
        $parameters = array(
            'group_id'                                => $this->group_id,
            'action'                                  => 'updateConfiguration',
            'home-ease-onboarding'                    => 1,
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME => $token->getToken()
        );

        return $parameters;
    }
}
