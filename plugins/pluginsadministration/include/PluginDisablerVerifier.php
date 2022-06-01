<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\PluginsAdministration;

use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyString;

#[ConfigKeyCategory('Plugins administration')]
class PluginDisablerVerifier
{
    #[ConfigKey("Prevent disabling some plugins in the web UI")]
    #[ConfigKeyString('')]
    public const SETTING_CANNOT_DISABLE_PLUGINS_WEB_UI = 'sys_plugins_that_can_not_be_disabled_from_the_web_ui';

    /**
     * @var string[]
     */
    private $untouchable_plugins_name;

    public function __construct(\PluginsAdministrationPlugin $plugin, $plugins_that_can_not_be_disabled_option)
    {
        $this->untouchable_plugins_name   = array_filter(
            array_map(
                'trim',
                explode(',', $plugins_that_can_not_be_disabled_option)
            )
        );
        $this->untouchable_plugins_name[] = $plugin->getName();
    }

    public function canPluginBeDisabled(\Plugin $plugin)
    {
        return ! in_array($plugin->getName(), $this->untouchable_plugins_name, true);
    }
}
