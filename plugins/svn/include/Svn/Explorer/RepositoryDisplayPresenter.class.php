<?php

/**
 * Copyright (c) Enalean, 2016. All rights reserved
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

namespace Tuleap\Svn\Explorer;

use Tuleap\Svn\Repository\Repository;
use Project;
use HTTPRequest;

class RepositoryDisplayPresenter {
    private $repository;
    public $repository_name;
    public $viewvc_html;

    public function __construct(Repository $repository, HTTPRequest $request, $viewvc_html) {
        $this->repository   = $repository;
        $this->help_message = $GLOBALS['Language']->getText('svn_intro', 'command_intro');
        $this->help_command = "svn checkout --username ".$request->getCurrentUser()->getName()." ".$this->repository->getSvnUrl();
        $this->viewvc_html  = $viewvc_html;
        $this->settings_url = SVN_BASE_URL .'/?'. http_build_query(array(
            'group_id' => $repository->getProject()->getID(),
            'action'   => 'settings',
            'repo_id'  => $repository->getId()
        ));
        $this->is_user_admin = $request->getProject()->userIsAdmin($request->getCurrentUser());
    }

    public function repository_name() {
        return $this->repository->getName();
    }

    public function svn_url() {
        return $this->repository->getSvnUrl();
    }
}