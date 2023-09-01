<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use Project;

final class ImportTemplateFormPresenter
{
    public readonly string $template_url;

    public readonly int $group_id;
    public readonly string $import_url;

    public function __construct(
        Project $project,
        public readonly bool $is_using_kanban_service,
        public readonly bool $is_legacy_agiledashboard,
    ) {
        $this->group_id = (int) $project->getID();

        $this->import_url = '/plugins/agiledashboard/?' . http_build_query([
            'group_id' => $project->getID(),
            'action'   => 'import-form',
        ]);

        $this->template_url = __DIR__ . '/../../resources/templates/scrum_dashboard_template.xml';
    }
}
