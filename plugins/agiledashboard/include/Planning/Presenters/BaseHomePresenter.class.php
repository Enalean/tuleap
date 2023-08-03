<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
    public $is_start_scrum_possible;

    public function __construct(
        $group_id,
        $is_user_admin,
        $is_mono_milestone_enabled,
        bool $is_planning_management_delegated,
    ) {
        $this->group_id                = $group_id;
        $this->is_user_admin           = $is_user_admin;
        $this->is_start_scrum_possible = ! $is_mono_milestone_enabled && ! $is_planning_management_delegated;
    }

    public function nothing_set_up()
    {
        if (! $this->is_user_admin) {
            return dgettext('tuleap-agiledashboard', 'The Agile Dashboard has not yet been configured by a project administrator.');
        }

        return dgettext('tuleap-agiledashboard', 'The Agile Dashboard has not yet been configured.');
    }

    public function nothing_set_up_admin_description()
    {
        return dgettext('tuleap-agiledashboard', 'Please choose between Scrum or Kanban layout below.<br>Don\'t worry, you will be able to change your mind and customize your configuration afterwards.');
    }

    public function come_back_later()
    {
        return dgettext('tuleap-agiledashboard', 'Please come back later.');
    }

    public function start_kanban()
    {
        return dgettext('tuleap-agiledashboard', 'Start Kanban');
    }

    public function start_scrum()
    {
        return dgettext('tuleap-agiledashboard', 'Start Scrum');
    }

    public function activate_scrum_v2()
    {
        return dgettext('tuleap-agiledashboard', 'Start Scrum V2');
    }

    public function create_scrum_url()
    {
        $params                      = $this->getBaseParameters();
        $params['activate-scrum']    = 1;
        $params['scrum-title-admin'] = 'Scrum';

        return '?' . http_build_query($params);
    }

    public function activate_kanban_url(): string
    {
        $params                    = $this->getBaseParameters();
        $params['activate-kanban'] = 1;

        return '?' . http_build_query($params);
    }

    private function getBaseParameters()
    {
        $token      = new CSRFSynchronizerToken('/plugins/agiledashboard/?action=admin');
        $parameters = [
            'group_id'                                => $this->group_id,
            'action'                                  => 'updateConfiguration',
            'home-ease-onboarding'                    => 1,
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME => $token->getToken(),
        ];

        return $parameters;
    }
}
