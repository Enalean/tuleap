<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\BotMattermostAgileDashboard\Presenter;

use CSRFSynchronizerToken;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactoryTest;

class AdminNotificationPresenter
{

    const DEFAULT_DURATION = '00:30';
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;
    public $bots;
    public $project_id;
    public $start_time;
    public $duration;

    public function __construct(CSRFSynchronizerToken $csrf_token, array $bots, $project_id, $start_time, $duration)
    {
        $this->csrf_token = $csrf_token;
        $this->bots       = $bots;
        $this->project_id = $project_id;
        $this->start_time = $start_time;
        $this->duration   = $duration;
    }

    public function title()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'admin_notification_title');
    }

    public function label_start_time()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'admin_notification_label_start_time');
    }

    public function label_duration()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'admin_notification_label_duration');
    }

    public function botListIsEmpty()
    {
        return count($this->bots) === 0;
    }

    public function empty_bot_list()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_empty_list');
    }

    public function table_col_name()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_table_col_name');
    }

    public function table_col_webhook_url()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_table_col_webhook_url');
    }

    public function table_col_channels()
    {
        return $GLOBALS['Language']->getText(
            'plugin_botmattermost_agiledashboard', 'admin_notification_table_col_channels'
        );
    }

    public function description()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'admin_notification_description');
    }

    public function has_start_time_and_duration()
    {
        return (isset($this->start_time) && isset($this->duration));
    }

    public function default_duration()
    {
        return self::DEFAULT_DURATION;
    }
}
