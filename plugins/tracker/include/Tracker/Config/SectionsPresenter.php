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

namespace Tuleap\Tracker\Config;

class SectionsPresenter
{

    public $emailgateway;
    public $natures;
    public $deprecation_panel;
    public $emailgateway_url;
    public $natures_url;
    public $deprecation_url;

    public function __construct()
    {
        $this->emailgateway       = $GLOBALS['Language']->getText('plugin_tracker_config', 'email_gateway');
        $this->natures            = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'title');
        $this->deprecation_panel = $GLOBALS['Language']->getText('plugin_tracker_deprecation_panel', 'deprecation_panel');

        $this->emailgateway_url = TRACKER_BASE_URL .'/config.php?'. http_build_query(array(
            'action'   => 'emailgateway'
        ));
        $this->natures_url = TRACKER_BASE_URL .'/config.php?'. http_build_query(array(
            'action'   => 'natures'
        ));
        $this->deprecation_url = TRACKER_BASE_URL .'/config.php?'. http_build_query(array(
            'action'   => 'deprecation'
        ));
    }
}
