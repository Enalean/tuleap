<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
use Tuleap\BotMattermost\Bot\Bot;

class AdminNotificationPresenter
{
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;
    public $system_bots;
    public $project_bots;
    public $project_id;
    public $bot_assigned;
    public $has_bots;
    public $has_system_bots;
    public $has_project_bots;
    public $title;
    public $description;
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
    public $any_configured_notification;
    public $any_configured_notification_tips;
    public $empty_bot_list;
    public $empty_channel_list;
    /**
     * @var string
     */
    public $time_format_regexp;

    /**
     * @var string
     */
    public $time_input_title;

    /**
     * @param Bot[] $system_bots
     * @param Bot[] $project_bots
     */
    public function __construct(
        CSRFSynchronizerToken $csrf_token,
        array $system_bots,
        array $project_bots,
        int $project_id,
        array $bot_assigned,
    ) {
        $this->csrf_token       = $csrf_token;
        $this->system_bots      = $system_bots;
        $this->project_bots     = $project_bots;
        $this->project_id       = $project_id;
        $this->bot_assigned     = $bot_assigned;
        $this->has_bots         = ! empty($system_bots) || ! empty($project_bots);
        $this->has_system_bots  = ! empty($system_bots);
        $this->has_project_bots = ! empty($project_bots);

        $this->title                  = dgettext('tuleap-botmattermost_agiledashboard', 'Mattermost notifications');
        $this->description            = dgettext('tuleap-botmattermost_agiledashboard', 'Choose a bot to send a summary of the stand-up in Mattermost.');
        $this->description_create_bot = dgettext('tuleap-botmattermost_agiledashboard', 'If you don\'t see a Bot linked to your Mattermost project/team, please contact your administrator.');

        $this->table_col_name     = dgettext('tuleap-botmattermost_agiledashboard', 'Name');
        $this->table_col_channels = dgettext('tuleap-botmattermost_agiledashboard', 'Channels');

        $this->button_config  = dgettext('tuleap-botmattermost_agiledashboard', 'Add notification');
        $this->button_close   = dgettext('tuleap-botmattermost_agiledashboard', 'Cancel');
        $this->button_delete  = dgettext('tuleap-botmattermost_agiledashboard', 'Delete');
        $this->button_edit    = dgettext('tuleap-botmattermost_agiledashboard', 'Edit');
        $this->button_confirm = dgettext('tuleap-botmattermost_agiledashboard', 'Add');
        $this->button_save    = dgettext('tuleap-botmattermost_agiledashboard', 'Update');

        $this->modal_add_title      = dgettext('tuleap-botmattermost_agiledashboard', 'Add notification');
        $this->modal_edit_title     = dgettext('tuleap-botmattermost_agiledashboard', 'Edit notification');
        $this->modal_delete_title   = dgettext('tuleap-botmattermost_agiledashboard', 'Delete notification');
        $this->modal_delete_content = dgettext('tuleap-botmattermost_agiledashboard', 'You are about to remove the notification. Please confirm your action.');

        $this->label_bot_name         = dgettext('tuleap-botmattermost_agiledashboard', 'Bot name');
        $this->label_bot_list         = dgettext('tuleap-botmattermost_agiledashboard', 'Bot list:');
        $this->label_send_time        = dgettext('tuleap-botmattermost_agiledashboard', 'Stand-up summary report time');
        $this->label_channels_handles = dgettext('tuleap-botmattermost_agiledashboard', 'Channel handles list');
        $this->input_channels_handles = dgettext('tuleap-botmattermost_agiledashboard', 'channel1, channel2, channel3');

        $this->purified_info_channels_handles = Codendi_HTMLPurifier::instance()->purify(
            dgettext('tuleap-botmattermost_agiledashboard', 'The channel handle is display in its URL<br>example: https://example.com/myGroup/channels/mychannel<br>handle: mychannel'),
            CODENDI_PURIFIER_LIGHT
        );
        $this->alert_time_warning             = sprintf(dgettext('tuleap-botmattermost_agiledashboard', 'The specified time must be adapted according to the server\'s time zone %1$s'), date_default_timezone_get());

        $this->empty_bot_list = dgettext('tuleap-botmattermost_agiledashboard', 'No bots are defined for the project (either system or project bots). The notification configuration is not available.');

        $this->any_configured_notification      = dgettext('tuleap-botmattermost_agiledashboard', 'The Mattermost notification has not yet been configured.');
        $this->any_configured_notification_tips = dgettext('tuleap-botmattermost_agiledashboard', 'To begin, click on add notification button below.');
        $this->empty_channel_list               = dgettext('tuleap-botmattermost_agiledashboard', 'No channel selected, the channel defined at the webhook creation will be used as default');

        $this->time_format_regexp = "^(0[0-9]|1[0-9]|2[0-3]|[0-9]):[0-5][0-9]$";
        $this->time_input_title   = dgettext('tuleap-botmattermost_agiledashboard', 'Time format: "hh:mm"');
    }
}
