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

namespace Tuleap\SVN\AccessControl;

use ForgeConfig;
use Tuleap\SVNCore\Repository;
use Tuleap\SVN\Admin\SectionsPresenter;
use Tuleap\SVN\Admin\BaseAdminPresenter;
use CSRFSynchronizerToken;
use Tuleap\SVNCore\SVNAccessFileContent;

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
        string $title,
        SVNAccessFileContent $contents,
        array $versions,
        $current_version_number,
        $last_version_number,
    ) {
        parent::__construct();

        $this->default_content = $contents->default;

        $this->csrf                  = $token->fetchHTMLInput();
        $this->repository            = $repository;
        $this->project_id            = $repository->getProject()->getID();
        $this->auth_file             = $contents->project_defined;
        $this->versions              = $versions;
        $this->repository_id         = $this->repository->getId();
        $this->repository_name       = $this->repository->getName();
        $this->repository_full_name  = $repository->getFullName();
        $this->title                 = $title;
        $this->access_control_active = true;

        $this->edit_access_file_title   = dgettext('tuleap-svn', 'Edit access control file');
        $this->default_text             = sprintf(dgettext('tuleap-svn', 'The default policy is to allow read-write access to all project members on the entire repository and read-only access to all other %1$s users. You can tune or even redefine the access permissions below to suit your needs.'), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME));
        $this->permissions_warning      = dgettext('tuleap-svn', 'It is recommended to always define permissions on a user group. Applying permissions to a manually defined group or to a specific user could cause security issues.');
        $this->button_new_version_label = dgettext('tuleap-svn', 'Save a new version');
        $this->select_version           = dgettext('tuleap-svn', 'Select a version');
        $this->saved_on                 = dgettext('tuleap-svn', 'saved on');
        $this->use_version              = dgettext('tuleap-svn', 'Use this selected version');
        $this->saved_versions           = dgettext('tuleap-svn', 'Saved versions:');

        $this->sections = new SectionsPresenter($repository);

        $this->current_version_title = sprintf(dgettext('tuleap-svn', 'You are using the last version (#%1$s):'), $current_version_number);

        if ($current_version_number !== $last_version_number) {
            $this->current_version_title = sprintf(dgettext('tuleap-svn', 'You are using a previous version (#%1$s):'), $current_version_number);
        }
    }
}
