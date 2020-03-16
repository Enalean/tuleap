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

class SVN_GeneralSettingsPresenter
{

    /** @var Project */
    private $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function svn_tracked()
    {
        return $this->project->isSVNTracked();
    }

    public function svn_mandatory_ref()
    {
        return $this->project->isSVNMandatoryRef();
    }

    public function svn_preamble()
    {
        return $this->project->getSVNPreamble();
    }

    public function svn_can_change_log()
    {
        return $this->project->canChangeSVNLog();
    }

    public function title()
    {
        return $GLOBALS['Language']->getText('svn_admin_general_settings', 'gen_settings');
    }

    public function tracking_title()
    {
        return $GLOBALS['Language']->getText('svn_admin_general_settings', 'tracking');
    }

    public function tracking_comment()
    {
        return $GLOBALS['Language']->getText('svn_admin_general_settings', 'tracking_comment', $GLOBALS['sys_name']);
    }

    public function on()
    {
        return $GLOBALS['Language']->getText('global', 'on');
    }

    public function off()
    {
        return $GLOBALS['Language']->getText('global', 'off');
    }

    public function mandatory_ref_title()
    {
        return $GLOBALS['Language']->getText('svn_admin_general_settings', 'mandatory_ref');
    }

    public function mandatory_ref_comment()
    {
        return $GLOBALS['Language']->getText('svn_admin_general_settings', 'mandatory_ref_comment');
    }

    public function svn_can_change_log_title()
    {
        return $GLOBALS['Language']->getText('svn_admin_general_settings', 'svn_can_change_log');
    }

    public function svn_can_change_log_comment()
    {
        return $GLOBALS['Language']->getText('svn_admin_general_settings', 'svn_can_change_log_comment');
    }

    public function preamble_title()
    {
        return $GLOBALS['Language']->getText(
            'svn_admin_general_settings',
            'preamble',
            array('/svn/?func=info&group_id=' . $this->project->getID(), $GLOBALS['sys_name'])
        );
    }

    public function btn_submit()
    {
        return $GLOBALS['Language']->getText('global', 'btn_submit');
    }

    public function project_id()
    {
        return $this->project->getID();
    }
}
