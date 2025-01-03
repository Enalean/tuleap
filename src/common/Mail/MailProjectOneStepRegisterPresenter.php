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

class MailProjectOneStepRegisterPresenter extends MailOutlinePresenter
{
    /**
     * @var Project
     */
    private $project;

    public function __construct(
        Project $project,
        $color_logo,
        string $logo_url,
    ) {
        parent::__construct(
            $logo_url,
            $this->get_title(),
            $this->get_thanks(),
            $this->get_signature(),
            $color_logo
        );
        $this->project = $project;
    }

    public function get_title()
    {
        return $GLOBALS['Language']->getText('mail_register_project_one_step', 'title');
    }

    public function get_thanks()
    {
        return $GLOBALS['Language']->getText('mail_outline', 'thanks', [ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME)]);
    }

    public function get_signature()
    {
        return $GLOBALS['Language']->getText('mail_outline', 'signature', [ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME)]);
    }

    public function get_project_full_name()
    {
        return $this->project->getPublicName();
    }

    public function get_project_short_name()
    {
        return $this->project->getUnixName();
    }

    public function get_project_summary_page()
    {
        return \Tuleap\ServerHostname::HTTPSUrl() . '/projects/' . $this->project->getUnixName();
    }

    public function get_project_full_name_title()
    {
        return $GLOBALS['Language']->getText('mail_register_project_one_step', 'full_name');
    }

    public function get_project_short_name_title()
    {
        return $GLOBALS['Language']->getText('mail_register_project_one_step', 'short_name');
    }

    public function get_project_summary_page_title()
    {
        return $GLOBALS['Language']->getText('mail_register_project_one_step', 'summary_page');
    }

    public function get_section_one()
    {
        return $GLOBALS['Language']->getOverridableText('mail_register_project_one_step', 'section_one', [$this->getUrlDocumentationSite(), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME)]);
    }

    public function get_section_two()
    {
        return $GLOBALS['Language']->getOverridableText('mail_register_project_one_step', 'section_two', [$this->getUrlSummaryLink()]);
    }

    public function get_section_three()
    {
        return $GLOBALS['Language']->getOverridableText('mail_register_project_one_step', 'section_three', [$this->getUrlProjectAdministration()]);
    }

    public function get_help()
    {
        return $GLOBALS['Language']->getText('mail_outline', 'help', [ForgeConfig::get('sys_email_admin')]);
    }

    public function getUrlSummaryLink()
    {
        return \Tuleap\ServerHostname::HTTPSUrl() . '/projects/' . $this->project->getUnixName();
    }

    private function getUrlDocumentationSite()
    {
        return \Tuleap\ServerHostname::HTTPSUrl() . '/help/';
    }

    public function getUrlProjectAdministration()
    {
        return \Tuleap\ServerHostname::HTTPSUrl() . '/project/admin/?group_id=' . $this->project->getID();
    }

    public function getMessageText()
    {
        $message = $this->get_title() . "\n\n"
               . $this->get_project_full_name_title() . ' ' . $this->get_project_full_name() . "\n"
               . $this->get_project_short_name_title() . ' ' . $this->get_project_short_name() . "\n"
               . $this->get_project_summary_page_title() . ' ' . $this->get_project_summary_page() . "\n\n"
               . $this->get_section_one() . "\n\n"
               . $this->get_section_two() . "\n\n"
               . $this->get_section_three() . "\n\n"
               . $this->get_thanks() . "\n\n"
               . $this->get_signature() . "\n\n";
        return $message;
    }
}
