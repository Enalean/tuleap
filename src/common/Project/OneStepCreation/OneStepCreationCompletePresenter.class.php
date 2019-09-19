<?php
/**
  * Copyright (c) Enalean, 2014. All rights reserved
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

class Project_OneStepCreation_OneStepCreationCompletePresenter
{

    public function page_title()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'complete_title');
    }

    public function wait()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'complete_wait');
    }

    public function thanks()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'complete_thanks', array(ForgeConfig::get('sys_name')));
    }
}
