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

namespace Tuleap\Theme\BurningParrot;

use Codendi_HTMLPurifier;
use Project;
use Tuleap\Project\Banner\BannerDisplay;

class CurrentProjectNavbarInfoPresenter
{
    /**
     * @var int
     */
    public $project_id;
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
    public $project_flags_title;

    /**
     * @var string
     */
    public $purified_banner = '';

    /**
     * @var bool
     */
    public $project_banner_is_visible = false;

    public function __construct(
        Project $project,
        array $project_flags,
        ?BannerDisplay $banner
    ) {
        $purifier = Codendi_HTMLPurifier::instance();

        $this->project_id        = $project->getID();
        $this->project_flags     = $project_flags;
        $nb_project_flags        = count($project_flags);
        $this->has_project_flags = $nb_project_flags > 0;

        $this->project_flags_title = ngettext("Project flag", "Project flags", $nb_project_flags);

        if ($banner !== null) {
            $this->purified_banner = $purifier->purify(
                $banner->getMessage(),
                Codendi_HTMLPurifier::CONFIG_MINIMAL_FORMATTING_NO_NEWLINE
            );
            $this->project_banner_is_visible = $banner->isVisible();
        }
    }
}
