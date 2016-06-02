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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Deprecation;

use Tuleap\Tracker\Config\SectionsPresenter;
use ForgeConfig;

class DeprecationPresenter
{
    public $title;
    public $sections;
    public $projects;
    public $warning_deprecation;

    public function __construct($title, array $projects)
    {
        $this->sections                = new SectionsPresenter();
        $this->title                   = $title;
        $this->projects                = $projects;
        $this->warning_deprecation     = $GLOBALS['Language']->getText('plugin_tracker_deprecation_panel', 'warning_deprecation');
        $this->project_title           = $GLOBALS['Language']->getText('plugin_tracker_deprecation_panel', 'title_project');
        $this->tracker_title           = $GLOBALS['Language']->getText('plugin_tracker_deprecation_panel', 'title_tracker');
        $this->incremented_files_title = $GLOBALS['Language']->getText('plugin_tracker_deprecation_panel', 'title_field');
    }

    public function getBaseUrl()
    {
        $host = ForgeConfig::get('sys_default_domain');
        if (ForgeConfig::get('sys_force_ssl')) {
            $url = 'https://'. $host;
        } else {
            $url = 'http://'. $host;
        }
        return $url;
    }
}
