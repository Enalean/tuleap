<?php
/**
 * Copyright (c) Enalean, 2015 - 2017. All Rights Reserved.
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

class ProjectTruncatedEmailsPresenter
{
    public $can_configure_truncated_mail;

    /** @var Project */
    private $project;

    /** @var array */
    private $impacted_services_list;

    public function __construct(Project $project, array $impacted_services_list, $can_configure_truncated_mail)
    {
        $this->project                      = $project;
        $this->impacted_services_list       = $impacted_services_list;
        $this->can_configure_truncated_mail = $can_configure_truncated_mail;
    }

    public function truncated_emails_title()
    {
        return $GLOBALS['Language']->getText('project_admin_editgroupinfo', 'truncated_emails_title');
    }

    public function use_truncated_emails()
    {
        return $GLOBALS['Language']->getText('project_admin_editgroupinfo', 'use_truncated_emails');
    }

    public function project_uses_truncated_emails()
    {
        return (bool) $this->project->getTruncatedEmailsUsage();
    }

    public function impacted_services()
    {
        return implode(', ', $this->impacted_services_list);
    }

    public function truncated_emails_impacted_services_introduction()
    {
        return $GLOBALS['Language']->getText(
            'project_admin_editgroupinfo',
            'truncated_emails_impacted_services_introduction'
        );
    }

    public function has_impacted_services_enabled()
    {
        return count($this->impacted_services_list) > 0;
    }

    public function truncated_emails_impacted_services_no_available()
    {
        return $GLOBALS['Language']->getText(
            'project_admin_editgroupinfo',
            'truncated_emails_impacted_services_no_available'
        );
    }

    public function truncated_emails_impacted_services_impacted_services()
    {
        return $GLOBALS['Language']->getText(
            'project_admin_editgroupinfo',
            'truncated_emails_impacted_services_impacted_services'
        );
    }
}
