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

class AdminAddBotPresenter {

    private $csrf_input;

    public function __construct(CSRFSynchronizerToken $csrf)
    {
        $this->csrf_input = $csrf->fetchHTMLInput();
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

    public function label_bot_name()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_label_bot_name');
    }

    public function label_hook_url()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_label_hook_url');
    }

    public function label_avatar_url()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_label_avatar_url');
    }

    public function label_channels_names()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_label_channels_names');
    }

    public function input_bot_name()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_input_bot_name');
    }

    public function input_url()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_input_url');
    }

    public function text_area_channels_names_help()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_text_area_channels_names_help');
    }

    public function button_submit()
    {
        return $GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_button_submit');
    }
}
