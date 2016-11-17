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

namespace Tuleap\BotMattermost;

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

        $this->empty_bot_list = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_empty_list');

        $this->title              = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_title');
        $this->legend_bot         = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_legend_bot');
        $this->modal_edit_title   = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_modal_edit_title');
        $this->modal_add_title    = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_modal_add_title');
        $this->modal_delete_title = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_modal_delete_title');

        $this->table_title                = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_table_title');
        $this->table_col_id               = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_table_col_id');
        $this->table_col_name             = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_table_col_name');
        $this->table_col_webhook_url      = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_table_col_webhook_url');
        $this->table_col_avatar           = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_table_col_avatar');
        $this->table_col_channels_handles = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_table_col_channels_handles');

        $this->label_bot_id                    = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_label_bot_id');
        $this->label_bot_name                  = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_label_bot_name');
        $this->label_hook_url                  = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_label_hook_url');
        $this->label_avatar_url                = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_label_avatar_url');
        $this->label_channels_handles          = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_label_channels_handles');
        $this->input_bot_name                  = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_input_bot_name');
        $this->input_url                       = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_input_url');
        $this->input_channels_handles          = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_input_channels_handles');
        $this->text_area_channels_handles_help = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_text_area_channels_handles_help');
        $this->purified_info_channels_handles  = Codendi_HTMLPurifier::instance()->purify(
            $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_info_channels_handles'),
            CODENDI_PURIFIER_LIGHT
        );

        $this->button_add_bot = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_button_add_bot');
        $this->button_update  = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_button_update');
        $this->button_close   = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_button_close');
        $this->button_delete  = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_button_delete');
        $this->button_edit    = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_button_edit');

        $this->modal_delete_bot_content = $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_modal_delete_bot_content');

        $this->pattern_url = "https?://.+";
    }
}