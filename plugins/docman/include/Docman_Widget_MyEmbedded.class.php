<?php
/**
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2009. Xerox Codendi Team.
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

use Tuleap\Dashboard\User\UserDashboardController;

class Docman_Widget_MyEmbedded extends Docman_Widget_Embedded
{

    public function __construct($plugin_path)
    {
        parent::__construct(
            'plugin_docman_my_embedded',
            UserManager::instance()->getCurrentUser()->getId(),
            UserDashboardController::LEGACY_DASHBOARD_TYPE,
            $plugin_path
        );
    }
}
