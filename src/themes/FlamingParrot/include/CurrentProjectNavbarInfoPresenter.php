<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use Tuleap\Project\Banner\BannerDisplay;

class FlamingParrot_CurrentProjectNavbarInfoPresenter  // phpcs:ignore
{
    /**
     * @var string[]
     */
    public $project_flags;
    /**
     * @var bool
     */
    public $has_project_flags;
    /**
     * @var string
     */
    public $json_encoded_project_flags;
    /**
     * @var string
     */
    public $project_flags_title;
    /**
     * @var bool
     */
    public $has_project_banner = false;
    /**
     * @var bool
     */
    public $project_banner_is_visible = false;

    public function __construct(array $project_flags, ?BannerDisplay $banner)
    {
        $this->project_flags     = $project_flags;
        $nb_project_flags        = count($project_flags);
        $this->has_project_flags = $nb_project_flags > 0;

        $this->json_encoded_project_flags = \json_encode($project_flags);

        $this->project_flags_title = ngettext("Project flag", "Project flags", $nb_project_flags);

        if ($banner !== null) {
            $this->has_project_banner        = true;
            $this->project_banner_is_visible = $banner->isVisible();
        }
    }
}
