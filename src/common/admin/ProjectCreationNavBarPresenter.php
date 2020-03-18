<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Exception;
use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigDisplayController;

/**
 * @psalm-immutable
 */
class ProjectCreationNavBarPresenter
{
    public $moderation_is_active = false;
    public $templates_is_active  = false;
    public $webhooks_is_active   = false;
    public $fields_is_active     = false;
    public $categories_is_active = false;
    public $visibility_is_active = false;
    public $widgets_is_active    = false;

    public $are_trove_categories_enabled = false;

    public function __construct($who_is_active)
    {
        $this->are_trove_categories_enabled = \ForgeConfig::get('sys_use_trove') != 0;
        switch ($who_is_active) {
            case 'moderation':
                $this->moderation_is_active = true;
                break;
            case 'templates':
                $this->templates_is_active = true;
                break;
            case 'webhooks':
                $this->webhooks_is_active = true;
                break;
            case 'fields':
                $this->fields_is_active = true;
                break;
            case 'categories':
                $this->categories_is_active = true;
                break;
            case ProjectVisibilityConfigDisplayController::TAB_NAME:
                $this->visibility_is_active = true;
                break;
            case ProjectWidgetsConfigurationDisplayController::TAB_NAME:
                $this->widgets_is_active = true;
                break;
            default:
                throw new Exception('Must be implemented');
        }
    }
}
