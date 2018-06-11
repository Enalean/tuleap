<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Admin;

class ProjectCreationNavBarPresenter
{
    public $moderation_is_active = false;
    public $templates_is_active  = false;

    public function __construct($who_is_active)
    {
        switch ($who_is_active) {
            case 'moderation':
                $this->moderation_is_active = true;
                break;
            case 'templates':
                $this->templates_is_active = true;
                break;
            default:
                throw new Exception('Must be implemented');
        }
    }
}
