<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

class MailProjectNotificationPresenter extends MailOutlinePresenter
{

    /**
     * @var string
     */
    public $color_button;

    /**
     * @var Project
     */
    private $project;

    public function __construct(
        Project $project,
        $color_logo,
        $logo_url,
        $color_button
    ) {
        parent::__construct(
            $logo_url,
            $this->get_title(),
            $this->get_thanks(),
            $this->get_signature(),
            $color_logo
        );
        $this->project      = $project;
        $this->color_button = $color_button;
    }

    public function get_title()
    {
        return $GLOBALS['Language']->getText('mail_register_project_one_step_notification', 'title');
    }

    public function get_thanks()
    {
        return $GLOBALS['Language']->getText('mail_outline', 'thanks', array(ForgeConfig::get('sys_name')));
    }

    public function get_signature()
    {
        return $GLOBALS['Language']->getText('mail_outline', 'signature', array(ForgeConfig::get('sys_name')));
    }

    public function get_section_one()
    {
        return $GLOBALS['Language']->getText('mail_register_project_one_step_notification', 'section_one', array(ForgeConfig::get('sys_name')));
    }

    public function get_section_two()
    {
        return $GLOBALS['Language']->getText('mail_register_project_one_step_notification', 'section_two', array($this->project->getPublicName()));
    }

    public function get_section_three()
    {
        return $GLOBALS['Language']->getText('mail_register_project_one_step_notification', 'section_three');
    }

    public function get_redirect_button()
    {
        return $GLOBALS['Language']->getText('mail_register_project_one_step_notification', 'redirect_button');
    }

    public function get_help()
    {
        return $GLOBALS['Language']->getText('mail_outline', 'help', array(ForgeConfig::get('sys_email_admin')));
    }

    public function get_url()
    {
        return HTTPRequest::instance()->getServerUrl() . '/admin/groupedit.php?group_id=' . $this->project->getID();
    }

    public function getMessageText()
    {
        $message = $this->get_title() . "\n\n"
               . $this->get_section_one() . "\n\n"
               . $this->get_section_two() . "\n\n"
               . $this->get_section_three() . "\n\n"
               . "<" . $this->get_url() . ">\n\n"
               . $this->get_thanks() . "\n\n"
               . $this->get_signature() . "\n\n";
        return $message;
    }
}
