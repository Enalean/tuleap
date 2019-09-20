<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class SVN_AccessFile_Presenter
{
    /** @var Project */
    private $project;

    /** @var string */
    public $content;

    /** @var string */
    public $default_content;

    /** @var array */
    public $select_options;

    /** @var int */
    private $version_number;

    /** @var string*/
    public $current_version_title;

    public function __construct(Project $project, $content, $default_content, array $select_options, $version_number, $current_version_title)
    {
        $this->project               = $project;
        $this->content               = $content;
        $this->default_content       = $default_content;
        $this->select_options        = $select_options;
        $this->version_number        = $version_number;
        $this->current_version_title = $current_version_title;
    }

    public function project_id()
    {
        return $this->project->getID();
    }

    public function policy()
    {
        return $GLOBALS['Language']->getText('svn_admin_access_control', 'def_policy', $GLOBALS['sys_name']);
    }

    public function permissions_warning()
    {
        return $GLOBALS['Language']->getText('svn_admin_access_control', 'permissions_warning');
    }

    public function default_formatted_content()
    {
        return str_replace("\n", "<br>", $this->default_content);
    }

    public function button_new_version_label()
    {
        return $GLOBALS['Language']->getText('svn_admin_access_control', 'button_new_version');
    }

    public function button_other_version_label()
    {
        return $GLOBALS['Language']->getText('svn_admin_access_control', 'button_other_version');
    }

    public function access_ctrl_file()
    {
        return $GLOBALS['Language']->getText('svn_admin_access_control', 'access_ctrl_file');
    }

    public function access_form_title()
    {
        return $GLOBALS['Language']->getText('svn_admin_access_control', 'access_ctrl');
    }

    public function other_version_title()
    {
        return $GLOBALS['Language']->getText('svn_admin_access_control', 'other_versions');
    }

    public function version_number()
    {
        return $this->version_number;
    }

    public function saved_on()
    {
        return $GLOBALS['Language']->getText('svn_admin_access_control', 'saved_on');
    }

    public function select_version()
    {
        return $GLOBALS['Language']->getText('svn_admin_access_control', 'select_version');
    }
}
