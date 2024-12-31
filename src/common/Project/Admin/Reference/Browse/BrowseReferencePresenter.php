<?php
/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\Admin\Reference\Browse;

final readonly class BrowseReferencePresenter
{
    public string $trash_icon;
    public bool $has_external_references;
    public bool $has_warning_messages;
    public bool $has_project_references;
    public bool $has_system_references;

    /**
     * @param ProjectReferencePatternPresenter[] $system_references
     * @param ProjectReferencePatternPresenter[] $project_references
     * @param ExternalSystemReferencePresenter[] $external_system_references
     * @param string[] $warning_messages
     */
    public function __construct(
        public array $system_references,
        public array $project_references,
        public bool $is_template_project,
        public string $page_title,
        public string $create_reference_url,
        public array $external_system_references,
        public array $warning_messages,
    ) {
        $this->trash_icon              = util_get_image_theme('ic/trash.png');
        $this->has_external_references = count($this->external_system_references) > 0;
        $this->has_project_references  = count($this->project_references) > 0;
        $this->has_system_references   = count($this->system_references) > 0;
        $this->has_warning_messages    = count($this->warning_messages) > 0;
    }
}
