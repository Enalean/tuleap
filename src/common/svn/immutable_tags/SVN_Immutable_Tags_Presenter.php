<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class SVN_ImmutableTagsPresenter {

    /** @var Project */
    private $project;

    /** @var string */
    public $immutable_tags_whitelist;

    /** @var string */
    public $immutable_tags_path;

    public function __construct(Project $project, $immutable_tags_whitelist, $immutable_tags_path) {
        $this->project                  = $project;
        $this->immutable_tags_whitelist = $immutable_tags_whitelist;
        $this->immutable_tags_path      = $immutable_tags_path;
    }

    public function svn_allow_tag_immutable_title() {
        return $GLOBALS['Language']->getText('svn_admin_general_settings', 'svn_allow_tag_immutable');
    }

    public function svn_allow_tag_immutable_comment() {
        return $GLOBALS['Language']->getText('svn_admin_general_settings', 'svn_allow_tag_immutable_comment');
    }

    public function immutable_tag_configuration() {
        return $GLOBALS['Language']->getText('svn_admin_general_settings', 'immutable_tag_configuration');
    }

    public function whitelist() {
        return $GLOBALS['Language']->getText('svn_admin_general_settings', 'immutable_whitelist');
    }

    public function path() {
        return $GLOBALS['Language']->getText('svn_admin_general_settings', 'immutable_path');
    }

    public function btn_submit() {
        return $GLOBALS['Language']->getText('global','btn_submit');
    }

    public function project_id() {
        return $this->project->getID();
    }
}
