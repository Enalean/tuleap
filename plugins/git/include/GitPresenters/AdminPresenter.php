<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All rights reserved
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

use Tuleap\Git\GlobalAdmin\AdminExternalPanePresenter;

/**
 * @deprecated Use GlobalAdminTabsPresenter,
 * @see \Tuleap\Git\GlobalAdmin\GlobalAdminTabsPresenter
 */
abstract class GitPresenters_AdminPresenter
{
    public $project_id;

    /**
     * @var AdminExternalPanePresenter[]
     */
    public $external_pane_presenters = [];

    public function __construct($project_id, array $external_pane_presenters)
    {
        $this->project_id               = $project_id;
        $this->external_pane_presenters = $external_pane_presenters;
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

    abstract public function form_action(): string;

    public function csrf_token(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken($this->form_action());
    }
}
