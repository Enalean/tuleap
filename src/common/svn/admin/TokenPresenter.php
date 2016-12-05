<?php
/**
 * Copyright (c) Enalean, 2015-2016. All Rights Reserved.
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

namespace Tuleap\SvnCore\Admin;

use CSRFSynchronizerToken;

class TokenPresenter extends Presenter
{
    public $is_token_pane_active = true;

    /**
     * @var Project[]
     */
    public $allowed_projects;

    public $is_resource_restricted;

    public $allow_all_enabled = false;

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(array $allowed_projects, $is_ressource_restricted, CSRFSynchronizerToken $csrf_token)
    {
        $this->allowed_projects       = $allowed_projects;
        $this->is_resource_restricted = $is_ressource_restricted;
        $this->csrf_token             = $csrf_token;
    }

    public function there_is_no_project()
    {
        return count($this->allowed_projects) === 0;
    }

    public function update_allowed_projects_action()
    {
        return '/admin/svn/index.php?pane=token&action=update_project';
    }

    public function update_allowed_projects_action_csrf()
    {
        return $this->csrf_token->fetchHTMLInput();
    }

    public function resource_allowed_project_title()
    {
        return '';
    }

    public function resource_allowed_project_pane_title()
    {
        return $GLOBALS['Language']->getText('svn_tokens', 'allowed_project_pane_title');
    }

    public function resource_allowed_project_information()
    {
        return $GLOBALS['Language']->getText('svn_tokens', 'allowed_project_information');
    }

    public function resource_allowed_project_list()
    {
        return $GLOBALS['Language']->getText('svn_tokens', 'allowed_project_list');
    }

    public function resource_allowed_project_list_allow_placeholder()
    {
        return $GLOBALS['Language']->getText('svn_tokens', 'allowed_project_list_allow_placeholder');
    }

    public function resource_allowed_project_list_filter_placeholder()
    {
        return $GLOBALS['Language']->getText('svn_tokens', 'allowed_project_list_filter_placeholder');
    }

    public function resource_allowed_project_list_allow_project()
    {
        return $GLOBALS['Language']->getText('svn_tokens', 'allowed_project_list_allow_project');
    }

    public function resource_allowed_project_list_id()
    {
        return $GLOBALS['Language']->getText('svn_tokens', 'allowed_project_list_id');
    }

    public function resource_allowed_project_list_name()
    {
        return $GLOBALS['Language']->getText('svn_tokens', 'allowed_project_list_name');
    }

    public function resource_allowed_project_list_empty()
    {
        return $GLOBALS['Language']->getText('svn_tokens', 'allowed_project_list_empty');
    }

    public function resource_allowed_project_revoke_title()
    {
        return $GLOBALS['Language']->getText('svn_tokens', 'allowed_project_revoke_title');
    }

    public function resource_allowed_project_revoke_description()
    {
        return $GLOBALS['Language']->getText('svn_tokens', 'allowed_project_revoke_description');
    }

    public function resource_allowed_project_revoke_yes()
    {
        return $GLOBALS['Language']->getText('svn_tokens', 'allowed_project_revoke_yes');
    }

    public function resource_allowed_project_revoke_no()
    {
        return $GLOBALS['Language']->getText('svn_tokens', 'allowed_project_revoke_no');
    }

    public function resource_allowed_project_list_revoke_projects()
    {
        return $GLOBALS['Language']->getText('svn_tokens', 'allowed_project_list_revoke_projects');
    }

    public function resource_allowed_project_allow_all()
    {
        return '';
    }

    public function resource_allowed_project_allow_all_submit()
    {
        return '';
    }

    public function restricted_resource_action()
    {
        return '';
    }

    public function restricted_resource_action_csrf()
    {
        return '';
    }

    public function resource_allowed_project_filter_empty()
    {
        return $GLOBALS['Language']->getText('admin', 'allowed_projects_filter_empty');
    }
}
