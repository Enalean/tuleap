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

namespace Tuleap\Project\Service;

use CSRFSynchronizerToken;
use Project;

class ServicesPresenter
{
    /** @var array|ServicePresenter[] */
    public $services;
    /** @var CSRFSynchronizerToken */
    public $csrf;
    /** @var int */
    public $project_id;
    /** @var bool */
    public $is_default_template;
    /** @var int */
    public $minimal_rank;
    /** @var string */
    public $csrf_token_name;
    /** @var string */
    public $csrf_token;
    /** @var string */
    public $allowed_icons;

    /**
     * @param ServicePresenter[]    $services
     */
    public function __construct(Project $project, CSRFSynchronizerToken $csrf, array $services)
    {
        $this->services            = $services;
        $this->csrf_token_name     = $csrf->getTokenName();
        $this->csrf_token          = $csrf->getToken();
        $this->csrf                = $csrf;
        $this->project_id          = $project->getID();
        $this->is_default_template = (int) $project->getID() === Project::DEFAULT_TEMPLATE_PROJECT_ID;
        $this->minimal_rank        = $project->getMinimalRank() + 1;
        $this->allowed_icons       = ServiceIconValidator::getAllowedIconsJSON();
    }
}
