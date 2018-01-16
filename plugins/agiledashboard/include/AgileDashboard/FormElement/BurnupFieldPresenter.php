<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement;

use Tuleap\AgileDashboard\v1\Artifact\BurnupRepresentation;

class BurnupFieldPresenter
{
    /**
     * @var string
     */
    public $burnup_data;
    /**
     * @var int
     */
    public $project_id;
    /**
     * @var int
     */
    public $artifact_id;
    /**
     * @var bool
     */
    public $can_burnup_be_regenerated;
    /**
     * @var string
     */
    public $css_url;
    /**
     * @var string
     */
    public $user_locale;

    public function __construct(
        BurnupRepresentation $burnup_representation,
        \Tracker_Artifact $artifact,
        $can_burnup_be_regenerated,
        $css_url,
        $user_locale
    ) {
        $this->burnup_data               = json_encode($burnup_representation);
        $this->project_id                = $artifact->getTracker()->getProject()->getId();
        $this->artifact_id               = $artifact->getId();
        $this->can_burnup_be_regenerated = $can_burnup_be_regenerated;
        $this->css_url                   = $css_url;
        $this->user_locale               = $user_locale;
    }
}
