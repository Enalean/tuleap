<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

use AgileDashBoard_Semantic_InitialEffort;
use PFUser;
use Planning_ArtifactMilestone;
use Planning_Milestone;
use Tracker_FormElementFactory;

class MilestoneBurndownFieldChecker
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    public function __construct(Tracker_FormElementFactory $formelement_factory)
    {
        $this->formelement_factory = $formelement_factory;
    }

    public function hasUsableBurndownField(PFUser $user, Planning_ArtifactMilestone $milestone): bool
    {
        $tracker = $milestone->getArtifact()->getTracker();
        $factory = $this->formelement_factory;

        $duration_field       = $factory->getFormElementByName($tracker->getId(), Planning_Milestone::DURATION_FIELD_NAME);
        $initial_effort_field = AgileDashBoard_Semantic_InitialEffort::load($tracker)->getField();

        return $factory->getABurndownField($user, $tracker)
            && $initial_effort_field
            && $initial_effort_field->isUsed()
            && $duration_field
            && $duration_field->isUsed();
    }
}
