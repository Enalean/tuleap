<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\v1\Artifact\BurnupRepresentation;

class BurnupFieldPresenter
{
    private const EFFORT_MODE         = 'effort';
    private const COUNT_ELEMENTS_MODE = 'count';

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
    public $user_locale;
    /**
     * @var string
     */
    public $warning;
    /**
     * @var bool
     */
    public $has_warning;

    /**
     * @var string
     */
    public $burnup_mode;

    public function __construct(
        CountElementsModeChecker $mode_checker,
        BurnupRepresentation $burnup_representation,
        \Tuleap\Tracker\Artifact\Artifact $artifact,
        $can_burnup_be_regenerated,
        $user_locale,
        $warning,
    ) {
        $this->burnup_data               = json_encode($burnup_representation);
        $this->project_id                = $artifact->getTracker()->getProject()->getId();
        $this->artifact_id               = $artifact->getId();
        $this->can_burnup_be_regenerated = $can_burnup_be_regenerated;
        $this->user_locale               = $user_locale;
        $this->warning                   = $warning;
        $this->has_warning               = $warning !== "";

        $this->burnup_mode = self::EFFORT_MODE;
        if ($mode_checker->burnupMustUseCountElementsMode($artifact->getTracker()->getProject())) {
            $this->burnup_mode = self::COUNT_ELEMENTS_MODE;
        }
    }
}
