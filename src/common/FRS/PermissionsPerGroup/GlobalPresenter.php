<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\FRS\PermissionsPerGroup;

use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPanePresenter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupLoadAllButtonPresenter;

class GlobalPresenter
{
    /**
     * @var PermissionPerGroupPanePresenter
     */
    public $service_presenter;
    /**
     * @var PermissionPerGroupLoadAllButtonPresenter
     */
    public $package_load_all_presenter;

    public function __construct(
        PermissionPerGroupPanePresenter $service_presenter,
        PermissionPerGroupLoadAllButtonPresenter $package_load_all_presenter
    ) {
        $this->service_presenter          = $service_presenter;
        $this->package_load_all_presenter = $package_load_all_presenter;
    }
}
