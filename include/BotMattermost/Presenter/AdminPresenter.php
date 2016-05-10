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

namespace Tuleap\BotMattermost\Presenter;

use CSRFSynchronizerToken;

class AdminPresenter
{

    private $csrf_input;
    private $bots;

    /**
     * @param Bot[] $bots
     */
    public function __construct(CSRFSynchronizerToken $csrf, array $bots)
    {
        $this->csrf_input = $csrf->fetchHTMLInput();
        $this->bots       = $bots;
    }

    public function csrf_input()
    {
        return $this->csrf_input;
    }

    public function title()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_title');
    }

    public function legend_bot()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_legend_bot');
    }

    public function button_add_bot()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_button_add_bot');
    }

    public function bots()
    {
        return $this->bots;
    }

    public function has_bots()
    {
        return count($this->bots) > 0;
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

    public function table_col_avatar()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_table_col_avatar');
    }

    public function table_col_channels()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_table_col_channels');
    }

    public function table_title()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_table_title');
    }

    public function confirm_delete_bot()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_confirm_delete_bot');
    }

    public function delete()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'delete');
    }
}
