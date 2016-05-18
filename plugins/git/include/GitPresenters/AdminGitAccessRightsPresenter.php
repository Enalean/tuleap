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

namespace Tuleap\Git\GitPresenters;

use GitPresenters_AdminPresenter;
use Git;
use CSRFSynchronizerToken;

class AdminGitAccessRightsPresenter extends GitPresenters_AdminPresenter
{

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf;

    public $read_options;
    public $write_options;
    public $rewrite_options;

    public function __construct(
        $project_id,
        $are_mirrors_defined,
        CSRFSynchronizerToken $csrf,
        array $read_options,
        array $write_options,
        array $rewrite_options
    ) {
        parent::__construct($project_id, $are_mirrors_defined);
        $this->manage_default_access_rights = true;

        $this->read_options    = $read_options;
        $this->write_options   = $write_options;
        $this->rewrite_options = $rewrite_options;
        $this->csrf            = $csrf;
    }

    public function default_git_access_rights()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_tab_default_access_rights');
    }

    public function csrf_token()
    {
        return $this->csrf->fetchHTMLInput();
    }

    public function default_access_rights_form_action()
    {
        return '/plugins/git/?group_id='. $this->project_id .'&action=admin-default-access_rights';
    }

    public function submit_default_access_rights()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'admin_save_submit');
    }

    public function is_control_limited()
    {
        return false;
    }

    public function label_read()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'perm_R');
    }

    public function label_write()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'perm_W');
    }

    public function label_rw()
    {
        return $GLOBALS['Language']->getText('plugin_git', 'perm_W+');
    }

    public function read_select_box_id()
    {
        return 'default_access_rights['.Git::DEFAULT_PERM_READ.']';
    }

    public function write_select_box_id()
    {
        return 'default_access_rights['.Git::DEFAULT_PERM_WRITE.']';
    }

    public function rewrite_select_box_id()
    {
        return 'default_access_rights['.Git::DEFAULT_PERM_WPLUS.']';
    }
}