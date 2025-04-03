<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap\Widget;

use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;

class RoadmapWidgetPresenterBuilder
{
    private TypePresenterFactory $nature_presenter_factory;
    private \TrackerFactory $tracker_factory;

    public function __construct(TypePresenterFactory $nature_presenter_factory, \TrackerFactory $tracker_factory)
    {
        $this->nature_presenter_factory = $nature_presenter_factory;
        $this->tracker_factory          = $tracker_factory;
    }

    public function getPresenter(
        int $roadmap_id,
        ?int $lvl1_iteration_tracker_id,
        ?int $lvl2_iteration_tracker_id,
        string $default_timescale,
        \PFUser $user,
    ): RoadmapWidgetPresenter {
        $visible_natures = array_filter(
            $this->nature_presenter_factory->getOnlyVisibleTypes(),
            static function (TypePresenter $nature) {
                return $nature->shortname !== ArtifactLinkField::TYPE_IS_CHILD;
            }
        );

        return new RoadmapWidgetPresenter(
            $roadmap_id,
            $visible_natures,
            $this->shouldLoadIterations($lvl1_iteration_tracker_id, $user),
            $this->shouldLoadIterations($lvl2_iteration_tracker_id, $user),
            $default_timescale
        );
    }

    private function shouldLoadIterations(?int $iteration_tracker_id, \PFUser $user): bool
    {
        if (! $iteration_tracker_id) {
            return false;
        }

        $tracker = $this->tracker_factory->getTrackerById($iteration_tracker_id);

        return $tracker && $tracker->isActive() && $tracker->userCanView($user);
    }
}
