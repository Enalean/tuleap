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

namespace Tuleap\BotMattermost\Presenter;

use Codendi_HTMLPurifier;
use CSRFSynchronizerToken;

class AdminPresenter
{

    public $csrf_token;
    public $bots;
    public $has_bots;
    public $empty_bot_list;
    public $title;
    public $legend_bot;
    public $modal_edit_title;
    public $modal_add_title;
    public $modal_delete_title;
    public $table_title;
    public $table_col_id;
    public $table_col_name;
    public $table_col_webhook_url;
    public $table_col_avatar;
    public $table_col_channels;
    public $label_bot_name;
    public $label_hook_url;
    public $label_avatar_url;
    public $label_channels_names;
    public $input_bot_name;
    public $input_url;
    public $text_area_channels_names_help;
    public $button_add_bot;
    public $button_update;
    public $button_close;
    public $button_delete;
    public $button_edit;
    public $confirm_delete_bot;
    public $pattern_url;
    public $modal_delete_bot_content;

    public function __construct(CSRFSynchronizerToken $csrf_token, array $bots)
    {
        $this->csrf_token = $csrf_token;
        $this->bots       = $bots;

        $this->has_bots = count($this->bots) > 0;

        $this->empty_bot_list = dgettext('tuleap-botmattermost', 'No results');

        $this->title              = dgettext('tuleap-botmattermost', 'Bot Mattermost configuration');
        $this->legend_bot         = dgettext('tuleap-botmattermost', 'Bot Configuration');
        $this->modal_edit_title   = dgettext('tuleap-botmattermost', 'Edit bot');
        $this->modal_add_title    = dgettext('tuleap-botmattermost', 'Add bot');
        $this->modal_delete_title = dgettext('tuleap-botmattermost', 'Delete bot');

        $this->table_title                = dgettext('tuleap-botmattermost', 'Bots list');
        $this->table_col_id               = dgettext('tuleap-botmattermost', 'Id');
        $this->table_col_name             = dgettext('tuleap-botmattermost', 'Name');
        $this->table_col_webhook_url      = dgettext('tuleap-botmattermost', 'Webhook URL');
        $this->table_col_avatar           = dgettext('tuleap-botmattermost', 'Avatar');
        $this->table_col_channels_handles = dgettext('tuleap-botmattermost', 'Channels handles');

        $this->label_bot_id                    = dgettext('tuleap-botmattermost', 'Id');
        $this->label_bot_name                  = dgettext('tuleap-botmattermost', 'Bot name');
        $this->label_hook_url                  = dgettext('tuleap-botmattermost', 'Webhook URL');
        $this->label_avatar_url                = dgettext('tuleap-botmattermost', 'Avatar URL');
        $this->label_channels_handles          = dgettext('tuleap-botmattermost', 'Channel handles list');
        $this->input_bot_name                  = dgettext('tuleap-botmattermost', 'Bot name...');
        $this->input_url                       = dgettext('tuleap-botmattermost', 'https://...');
        $this->input_channels_handles          = dgettext('tuleap-botmattermost', 'Channel1
Channel2
Channel3');
        $this->text_area_channels_handles_help = dgettext('tuleap-botmattermost', 'Add one channel name per row');
        $this->purified_info_channels_handles  = Codendi_HTMLPurifier::instance()->purify(
            dgettext('tuleap-botmattermost', 'The channel handle is display in its URL<br>example: https://example.com/myGroup/channels/mychannel<br>handle: mychannel'),
            CODENDI_PURIFIER_LIGHT
        );

        $this->button_add_bot = dgettext('tuleap-botmattermost', 'Add bot');
        $this->button_update  = dgettext('tuleap-botmattermost', 'Update');
        $this->button_close   = dgettext('tuleap-botmattermost', 'Cancel');
        $this->button_delete  = dgettext('tuleap-botmattermost', 'Delete');
        $this->button_edit    = dgettext('tuleap-botmattermost', 'Edit');

        $this->modal_delete_bot_content = dgettext('tuleap-botmattermost', 'You are about to remove a bot. Please confirm your action.');

        $this->pattern_url = "https?://.+";
    }
}
