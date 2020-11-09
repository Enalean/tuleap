<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All rights reserved
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

namespace Tuleap\SVN\Admin;

use Project;
use CSRFSynchronizerToken;

class BaseGlobalAdminPresenter
{
    /**
     * @var bool
     * @psalm-readonly
     */
    public $admin_groups_active;
    /**
     * @var string
     * @psalm-readonly
     */
    public $admin_groups_url;
    /**
     * @var string
     * @psalm-readonly
     */
    public $admin_groups;
    /**
     * @var int|null
     * @psalm-readonly
     */
    public $project_id;
    /**
     * @var string
     * @psalm-readonly
     */
    public $csrf_input;
    /**
     * @var string
     * @psalm-readonly
     */
    public $submit;
    /**
     * @var string
     * @psalm-readonly
     */
    public $title;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $has_migrate_from_core = false;
    /**
     * @var string
     * @psalm-readonly
     */
    public $migrate_from_core_url;

    public function __construct(Project $project, CSRFSynchronizerToken $token, bool $has_migrate_from_core)
    {
        $this->project_id = $project->getId();

        $this->admin_groups_active = false;
        $this->admin_groups_url    = GlobalAdministratorsController::getURL($project);
        $this->admin_groups        = dgettext('tuleap-svn', 'Admin Groups');

        $this->csrf_input          = $token->fetchHTMLInput();
        $this->title               = dgettext('tuleap-svn', 'SVN Administration');
        $this->submit              = dgettext('tuleap-svn', 'Save');

        $this->has_migrate_from_core = $has_migrate_from_core;
        $this->migrate_from_core_url = DisplayMigrateFromCoreController::getURL($project);
    }
}
