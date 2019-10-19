<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

namespace Tuleap\SVN\Admin;

class RestorePresenter
{
    public $restore_not_found;
    public $repositories;
    public $restore_confirm;
    public $project_id;
    public $title;
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;

    public function __construct(
        \CSRFSynchronizerToken $csrf_token,
        array $repositories,
        $project_id
    ) {
        $this->title             = dgettext('tuleap-svn', 'Deleted svn repositories');
        $this->repositories      = $repositories;
        $this->project_id        = $project_id;
        $this->csrf_token        = $csrf_token;
        $this->restore_not_found = dgettext('tuleap-svn', 'No restorable svn repositories found.');
        $this->restore_confirm   = dgettext('tuleap-svn', 'Confirm restore of the svn repository');
        $this->repository_name   = dgettext('tuleap-svn', 'Repository name');
        $this->deleted_date      = dgettext('tuleap-svn', 'Deleted date');
    }
}
