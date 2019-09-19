<?php
/**
 * Copyright (c) Enalean, 2013 - 2016. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

abstract class GitPresenters_AdminPresenter
{

    public $project_id;

    /** @var bool */
    public $are_mirrors_defined;
    public $manage_gerrit_templates                = false;
    public $manage_git_admins                      = false;
    public $manage_mass_update_select_repositories = false;
    public $manage_mass_update                     = false;
    public $manage_default_settings                = false;
    public $manage_default_access_rights           = false;

    public function __construct($project_id, $are_mirrors_defined)
    {
        $this->project_id          = $project_id;
        $this->are_mirrors_defined = $are_mirrors_defined;
    }

    public function git_admin()
    {
        return dgettext('tuleap-git', 'Git Administration');
    }

    public function tab_gerrit_templates()
    {
        return dgettext('tuleap-git', 'Gerrit Templates');
    }

    public function tab_git_admins()
    {
        return dgettext('tuleap-git', 'Git administrators');
    }

    public function tab_mass_update()
    {
        return dgettext('tuleap-git', 'Mass update of repositories');
    }

    public function tab_template_settings()
    {
        return dgettext('tuleap-git', 'Git settings template');
    }

    public function manage_mass_update_active()
    {
        return $this->manage_mass_update_select_repositories || $this->manage_mass_update;
    }
}
