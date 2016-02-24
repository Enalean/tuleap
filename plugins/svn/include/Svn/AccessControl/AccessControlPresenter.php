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

namespace Tuleap\Svn\AccessControl;

use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\Admin\SectionsPresenter;
use CSRFSynchronizerToken;

class AccessControlPresenter {

    public $title_acces_control;
    public $default_text;
    public $permissions_warning;
    public $button_new_version_label;
    public $default_content;
    public $repository;
    public $project_id;
    public $auth_file;
    public $versions;
    public $current_version_title;
    public $saved_on;
    public $use_version;
    public $select_version;
    public $saved_versions;

    public function __construct(
        CSRFSynchronizerToken $token,
        Repository $repository,
        $title,
        $default_content,
        $auth_file,
        array $versions,
        $current_version_number,
        $last_version_number
    ) {
        $this->default_content = $default_content;

        $this->csrf                  = $token->fetchHTMLInput();
        $this->repository            = $repository;
        $this->project_id            = $repository->getProject()->getId();
        $this->auth_file             = $auth_file;
        $this->versions              = $versions;
        $this->repo_id               = $this->repository->getId();
        $this->title                 = $title;

        $this->default_text             = $GLOBALS['Language']->getText('plugin_svn_admin', 'default_text', $GLOBALS['sys_name']);
        $this->permissions_warning      = $GLOBALS['Language']->getText('plugin_svn_admin', 'permissions_warning');
        $this->button_new_version_label = $GLOBALS['Language']->getText('plugin_svn_admin', 'button_new_version_label');
        $this->select_version           = $GLOBALS['Language']->getText('plugin_svn_admin', 'select_version');
        $this->saved_on                 = $GLOBALS['Language']->getText('plugin_svn_admin', 'saved_on');
        $this->use_version              = $GLOBALS['Language']->getText('plugin_svn_admin', 'use_version');
        $this->saved_versions           = $GLOBALS['Language']->getText('plugin_svn_admin', 'saved_versions');

        $this->sections = new SectionsPresenter($repository);


        $this->current_version_title = $GLOBALS['Language']->getText(
            'plugin_svn_admin',
            'last_version',
            $current_version_number
        );

        if ($current_version_number !== $last_version_number) {
            $this->current_version_title = $GLOBALS['Language']->getText(
                'plugin_svn_admin',
                'previous_version',
                $current_version_number
            );
        }
    }
}