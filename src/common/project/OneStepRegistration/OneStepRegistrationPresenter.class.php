<?php
/**
  * Copyright (c) Enalean, 2015. All rights reserved
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

/**
 * Presenter for one step registration project
 */
class Project_OneStepRegistration_OneStepRegistrationPresenter {

    const HEADER            = 'title';
    const SECTION_ONE       = 'section_one';
    const SECTION_TWO       = 'section_two';
    const THANKS            = 'thanks';
    const REDIRECT_CONTENT  = 'redirect_content';
    const REDIRECT_URL      = 'redirect_url';
    const TEXT              = 'register_confirmation_project_one_step';

    /**
     * @var Project
     */
    private $project;

    public function __construct(Project $project) {
        $this->project = $project;
    }

    public function get_title() {
        return $GLOBALS['Language']->getText(self::TEXT, self::HEADER);
    }

    public function get_section_one() {
        return $GLOBALS['Language']->getText(self::TEXT, self::SECTION_ONE);
    }

    public function get_section_two() {
        return $GLOBALS['Language']->getText(self::TEXT, self::SECTION_TWO);
    }

    public function get_redirect_url() {
        $project_name = end(explode('/', rtrim($this->project->getHomePage(), '/')));
        return get_server_url() . '/projects/' . $project_name;
    }

    public function get_redirect_content() {
        return $GLOBALS['Language']->getText(self::TEXT, self::REDIRECT_CONTENT);
    }

    public function get_thanks() {
        return $GLOBALS['Language']->getText(self::TEXT, self::THANKS, array(ForgeConfig::get('sys_name')));
    }
}
