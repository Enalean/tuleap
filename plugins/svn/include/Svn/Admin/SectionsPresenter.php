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

use Tuleap\Svn\Repository\Repository;

class SectionsPresenter {

    public $notifications;
    public $access_control;
    public $notifications_url;
    public $access_control_url;

    public function __construct(Repository $repository) {
        $this->notifications  = $GLOBALS['Language']->getText('plugin_svn', 'notifications');
        $this->access_control = $GLOBALS['Language']->getText('plugin_svn', 'access_control');

        $this->notifications_url = SVN_BASE_URL .'/?'. http_build_query(array(
            'group_id' => $repository->getProject()->getId(),
            'action'   => 'display-mail-notification',
            'repo_id'  => $repository->getId()
        ));
        $this->access_control_url = SVN_BASE_URL .'/?'. http_build_query(array(
            'group_id' => $repository->getProject()->getId(),
            'action'   => 'access-control',
            'repo_id'  => $repository->getId()
        ));
    }
}