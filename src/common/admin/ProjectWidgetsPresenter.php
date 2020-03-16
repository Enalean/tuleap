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
 */

declare(strict_types=1);

namespace Tuleap\Admin;

use Tuleap\Dashboard\Project\DisabledProjectWidgetsChecker;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Widget;

class ProjectWidgetsPresenter
{
    public $id;

    /**
     * @var string
     */
    public $title;

    /**
     * @var bool
     */
    public $is_disabled;

    /**
     * @var string
     */
    public $form_url;

    public function __construct(Widget $widget, DisabledProjectWidgetsChecker $checker)
    {
        $this->id           = (string) $widget->getId();
        $this->title        = (string) $widget->getTitle();
        $this->is_disabled  = $checker->isWidgetDisabled($widget, ProjectDashboardController::DASHBOARD_TYPE);
        $this->form_url     = $this->buildFormUrl();
    }

    private function buildFormUrl(): string
    {
        $base_url = '/admin/project-creation/widgets/' . urlencode($this->id);

        if ($this->is_disabled) {
            return $base_url . '/enable';
        } else {
            return $base_url . '/disable';
        }
    }
}
