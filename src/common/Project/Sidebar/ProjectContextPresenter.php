<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Sidebar;

use Codendi_HTMLPurifier;
use Project;
use Tuleap\Project\Banner\BannerDisplay;
use Tuleap\Project\Flags\ProjectFlagPresenter;
use Tuleap\Project\REST\v1\ProjectSidebarDataRepresentation;

/**
 * @psalm-immutable
 */
final class ProjectContextPresenter
{
    public int $project_id;
    public bool $has_project_banner;
    public bool $project_banner_is_visible;
    public string $purified_banner;
    /**
     * @var false|string
     */
    public $json_encoded_project_flags;

    /**
     * @param ProjectFlagPresenter[] $project_flags
     */
    private function __construct(
        Project $project,
        array $project_flags,
        ?BannerDisplay $banner,
        string $purified_banner,
        public ProjectSidebarDataRepresentation $sidebar_data,
    ) {
        $this->project_id                 = (int) $project->getID();
        $this->has_project_banner         = $banner !== null;
        $this->project_banner_is_visible  = $banner && $banner->isVisible();
        $this->purified_banner            = $purified_banner;
        $this->json_encoded_project_flags = json_encode($project_flags, JSON_THROW_ON_ERROR);
    }

    public static function build(
        Project $project,
        array $project_flags,
        ?BannerDisplay $banner,
        ProjectSidebarDataRepresentation $project_sidebar_data,
    ): self {
        $purified_banner = '';
        if ($banner) {
            $purified_banner = Codendi_HTMLPurifier::instance()->purify(
                $banner->getMessage(),
                Codendi_HTMLPurifier::CONFIG_MINIMAL_FORMATTING_NO_NEWLINE
            );
        }
        return new self(
            $project,
            $project_flags,
            $banner,
            $purified_banner,
            $project_sidebar_data,
        );
    }
}
