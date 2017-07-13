<?php
/**
 * Copyright (c) Enalean, 2016-2017. All Rights Reserved.
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

use Codendi_HTMLPurifier;
use CSRFSynchronizerToken;

class AdminNotificationPresenter
{

    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;
    public $bots;
    public $project_id;
    public $bot_assigned;
    public $title;
    public $table_col_name;
    public $table_col_channels;
    public $button_config;
    public $button_close;
    public $button_delete;
    public $button_edit;
    public $button_confirm;
    public $modal_add_title;
    public $modal_edit_title;
    public $modal_delete_title;
    public $modal_delete_content;
    public $label_send_time;
    public $label_channels_handles;
    public $input_channels_handles;
    public $purified_info_channels_handles;
    public $alert_time_warning;
    public $bot_list_is_empty;
    public $empty_bot_list;
    public $any_configured_notification;

    public function __construct(
        CSRFSynchronizerToken $csrf_token,
        array $bots,
        $project_id,
        $bot_assigned
    ){
        $this->csrf_token   = $csrf_token;
        $this->bots         = $bots;
        $this->project_id   = $project_id;
        $this->bot_assigned = $bot_assigned;

        $this->title           = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'admin_notification_title');

        $this->table_col_name        = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_table_col_name');
        $this->table_col_channels    = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'admin_notification_table_col_channels');

        $this->button_config  = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'button_configure_notification');
        $this->button_close   = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_button_close');
        $this->button_delete  = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_button_delete');
        $this->button_edit    = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_button_edit');
        $this->button_confirm = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'button_confirm');

        $this->modal_add_title      = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'modal_header_configure_notification');
        $this->modal_edit_title     = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'modal_header_edit_configure_notification');
        $this->modal_delete_title   = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'modal_header_delete_configure_notification');
        $this->modal_delete_content = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'modal_delete_content');

        $this->label_send_time        = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'admin_notification_label_send_time');
        $this->label_channels_handles = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'configuration_label_channels_handles');
        $this->input_channels_handles = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'configuration_input_channels_handles');

        $this->purified_info_channels_handles = Codendi_HTMLPurifier::instance()->purify(
                $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'configuration_info_channels_handles'),
                CODENDI_PURIFIER_LIGHT
            );
        $this->alert_time_warning             = $GLOBALS['Language']->getText(
            'plugin_botmattermost_agiledashboard',
            'admin_notification_time_warning',
            array(date_default_timezone_get())
        );

        $this->bot_list_is_empty = count($this->bots) === 0;
        $this->empty_bot_list    = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_empty_list');

        $this->any_configured_notification = $GLOBALS['Language']->getText('plugin_botmattermost_agiledashboard', 'any_configured_notification');
    }
}
