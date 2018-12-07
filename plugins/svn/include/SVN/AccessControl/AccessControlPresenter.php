<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All rights reserved
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

namespace Tuleap\SVN\AccessControl;

use Tuleap\SVN\Repository\Repository;
use Tuleap\SVN\Admin\SectionsPresenter;
use Tuleap\SVN\Admin\BaseAdminPresenter;
use CSRFSynchronizerToken;

class AccessControlPresenter extends BaseAdminPresenter
{

    public $edit_access_file_title;
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
    public $repository_id;
    public $repository_name;
    public $repository_full_name;
    public $title;
    public $csrf;
    public $sections;

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
        parent::__construct();

        $this->default_content = $default_content;

        $this->csrf                     = $token->fetchHTMLInput();
        $this->repository               = $repository;
        $this->project_id               = $repository->getProject()->getID();
        $this->auth_file                = $auth_file;
        $this->versions                 = $versions;
        $this->repository_id            = $this->repository->getId();
        $this->repository_name          = $this->repository->getName();
        $this->repository_full_name     = $repository->getFullName();
        $this->title                    = $title;
        $this->access_control_active    = true;

        $this->edit_access_file_title   = $GLOBALS['Language']->getText('plugin_svn_admin', 'edit_access_file_title');
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
