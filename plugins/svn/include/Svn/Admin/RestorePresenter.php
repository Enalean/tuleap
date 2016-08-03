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

class RestorePresenter
{
    public $restore_not_found;
    public $repositories;
    public $restore_confirm;
    public $project_id;
    public $url_icon;

    public function __construct(
        array $repositories,
        $project_id
    ) {
        $this->repositories      = $repositories;
        $this->project_id        = $project_id;
        $this->url_icon          = util_get_image_theme('ic/convert.png');
        $this->restore_not_found = $GLOBALS['Language']->getText('plugin_svn', 'restore_no_repo_found');
        $this->restore_confirm   = $GLOBALS['Language']->getText('plugin_svn', 'restore_confirmation');
    }

    public function hasRepositories()
    {
        return count($this->repositories) > 0;
    }
}