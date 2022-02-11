<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Layout\ProjectSidebar\Internationalization;

/**
 * @psalm-immutable
 */
final class ProjectSidebarInternationalization
{
    private function __construct(
        public string $tools,
        public string $homepage,
        public string $project_administration,
        public string $project_announcement,
        public string $show_project_announcement,
        public string $close_sidebar,
        public string $open_sidebar,
    ) {
    }

    public static function build(): self
    {
        return new self(
            _("Tools"),
            _("Homepage"),
            _("Project administration"),
            _("Project announcement"),
            _("Show project announcement"),
            _("Close sidebar"),
            _("Open sidebar"),
        );
    }
}
