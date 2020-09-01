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

        $this->selectbox_label              = dgettext('tuleap-hudson_svn', 'Trigger a build after a commit in repository:');
        $this->label_svn_multirepo          = dgettext('tuleap-hudson_svn', 'SVN multiple repositories');
        $this->svn_paths_helper             = dgettext('tuleap-hudson_svn', 'If empty, every commits will trigger a build.');
        $this->svn_paths_label              = dgettext('tuleap-hudson_svn', 'Only when commit occurs on following paths:');
        $this->svn_paths_placeholder        = dgettext('tuleap-hudson_svn', 'One path per line...');
        $this->params_header                = sprintf(dgettext('tuleap-hudson_svn', '%1$s will automatically pass following parameters to the job:'), ForgeConfig::get("sys_name"));

        $this->project_param_description    = BuildParams::BUILD_PARAMETER_PROJECT . ' : ' . dgettext('tuleap-hudson_svn', 'Identifier (String) of the current project.');
        $this->user_param_description       = BuildParams::BUILD_PARAMETER_USER . ' : ' . sprintf(dgettext('tuleap-hudson_svn', 'Identifier (String) of %1$s user who made the commit.'), ForgeConfig::get("sys_name"));
        $this->repository_param_description = BuildParams::BUILD_PARAMETER_REPOSITORY . ' : ' . dgettext('tuleap-hudson_svn', 'URL (String) of the updated repository (https://svn.example.com/svnplugin/ExampleDepot/).');
        $this->path_param_description       = BuildParams::BUILD_PARAMETER_PATH . ' : ' . dgettext('tuleap-hudson_svn', 'Value (string) of paths impacted by commit. Please note that paths are end of line separated.');
    }
}
