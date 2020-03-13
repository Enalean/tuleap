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

namespace Tuleap\HudsonSvn;

use ForgeConfig;

class FormPresenter
{

    /**
     * @var string
     */
    public $path;

    /**
     * @var array
     */
    public $repositories;

    /**
     * @var bool
     */
    public $is_checked;

    public $selectbox_label;
    public $svn_paths_helper;
    public $svn_paths_label;
    public $svn_paths_placeholder;
    public $label_svn_multirepo;
    public $params_header;
    public $project_param_description;
    public $user_param_description;
    public $repository_param_description;
    public $path_param_description;

    public function __construct(array $repositories, $is_checked, $path)
    {
        $this->repositories = $repositories;
        $this->is_checked   = $is_checked;
        $this->path         = $path;

        $this->selectbox_label              = $GLOBALS['Language']->getText('plugin_hudson_svn', 'selectbox_label');
        $this->label_svn_multirepo          = $GLOBALS['Language']->getText('plugin_hudson_svn', 'label_svn_multirepo');
        $this->svn_paths_helper             = $GLOBALS['Language']->getText('plugin_hudson', 'svn_paths_helper');
        $this->svn_paths_label              = $GLOBALS['Language']->getText('plugin_hudson', 'svn_paths_label');
        $this->svn_paths_placeholder        = $GLOBALS['Language']->getText('plugin_hudson', 'svn_paths_placeholder');
        $this->params_header                = $GLOBALS['Language']->getText('plugin_hudson_svn', 'params_header', ForgeConfig::get("sys_name"));

        $this->project_param_description    = BuildParams::BUILD_PARAMETER_PROJECT . ' : ' . $GLOBALS['Language']->getText('plugin_hudson_svn', 'project_param');
        $this->user_param_description       = BuildParams::BUILD_PARAMETER_USER . ' : ' . $GLOBALS['Language']->getText('plugin_hudson_svn', 'user_param', ForgeConfig::get("sys_name"));
        $this->repository_param_description = BuildParams::BUILD_PARAMETER_REPOSITORY . ' : ' . $GLOBALS['Language']->getText('plugin_hudson_svn', 'repository_param');
        $this->path_param_description       = BuildParams::BUILD_PARAMETER_PATH . ' : ' . $GLOBALS['Language']->getText('plugin_hudson_svn', 'path_param');
    }
}
