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

namespace Tuleap\Svn\Admin;

use Project;
use Tuleap\Svn\Repository\Repository;

class RepositoryDeletePresenter extends BaseAdminPresenter
{
    public $project_id;
    public $repository_name;
    public $repository_id;
    public $title;
    public $subtitle;
    public $comment;
    public $button;
    public $alert_message;
    public $sections;

    public function __construct(
        Repository $repository,
        Project $project,
        $title
    ) {
        parent::__construct();

        $this->project_id               = $project->getId();
        $this->repository_id            = $repository->getId();
        $this->repository_name          = $repository->getName();
        $this->title                    = $title;
        $this->repository_delete_active = true;
        $this->alert_message            = $GLOBALS['Language']->getText('plugin_svn_admin_repository_delete', 'alert_message');
        $this->subtitle                 = $GLOBALS['Language']->getText('plugin_svn_admin_repository_delete', 'subtitle');
        $this->comment                  = $GLOBALS['Language']->getText('plugin_svn_admin_repository_delete', 'comment');
        $this->button                   = $GLOBALS['Language']->getText('plugin_svn_admin_repository_delete', 'button_delete');

        $this->sections = new SectionsPresenter($repository);
    }
}
