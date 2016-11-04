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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Git_AdminGitoliteConfigPresenter  extends Git_AdminPresenter {

    public $manage_gitolite_config = true;
    public $gitolite_config_active = 'tlp-tab-active';
    public $regenerate_gitolite_configuration;



    public function __construct($title, CSRFSynchronizerToken $csrf_token) {
        parent::__construct($title, $csrf_token);

        $this->regenerate_gitolite_configuration = $GLOBALS['Language']->getText('plugin_git', 'regenerate_gitolite_configuration');
    }

    public function gitolite_config_title() {
        return $GLOBALS['Language']->getText('plugin_git', 'admin_gitolite_config_title');
    }

    public function gitolite_config_description() {
        return $GLOBALS['Language']->getText('plugin_git', 'admin_gitolite_config_description');
    }

    public function submit() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_git_admins_submit_button');
    }
}
