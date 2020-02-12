<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Project\Admin;

use EventManager;
use Project;

class TemplatePresenter
{
    /**
     * Add buttons in admin » project templates table
     *
     * Parameters:
     *  - template => (in) Project
     *  - buttons  => (out) Array of button presenters
     */
    public const EVENT_ADDITIONAL_ADMIN_BUTTONS = 'event_additional_admin_buttons';

    public $id;
    public $name;
    public $unix_name;
    public $additional_buttons;

    public function __construct(Project $template)
    {
        $this->id        = $template->getId();
        $this->name      = $template->getPublicName();
        $this->unix_name = $template->getUnixNameMixedCase();

        $this->additional_buttons = array();
        EventManager::instance()->processEvent(self::EVENT_ADDITIONAL_ADMIN_BUTTONS, array(
            'template' => $template,
            'buttons'  => &$this->additional_buttons
        ));
    }
}
