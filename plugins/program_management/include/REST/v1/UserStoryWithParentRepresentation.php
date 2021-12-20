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

namespace Tuleap\ProgramManagement\REST\v1;

use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\RetrieveFullTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\UserStory;
use Tuleap\ProgramManagement\Domain\TrackerNotFoundException;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;

/**
 * @psalm-immutable
 */
final class UserStoryWithParentRepresentation extends ElementRepresentation
{
    /**
     * @var bool
     */
    public $is_open;
    /**
     * @var ProjectReference
     */
    public $project;
    /**
     * @var MinimalTrackerRepresentation
     */
    public $tracker;
    /**
     * @var string
     */
    public $background_color;
    /**
     * @var ?MinimalFeatureRepresentation
     */
    public $feature;

    private function __construct(
        int $id,
        string $uri,
        string $xref,
        ?string $title,
        bool $is_open,
        ProjectReference $project,
        MinimalTrackerRepresentation $tracker,
        string $background_color,
        ?MinimalFeatureRepresentation $feature_representation,
    ) {
        parent::__construct($id, $uri, $xref, $title);
        $this->is_open          = $is_open;
        $this->project          = $project;
        $this->tracker          = $tracker;
        $this->background_color = $background_color;
        $this->feature          = $feature_representation;
    }

    /**
     * @throws TrackerNotFoundException
     */
    public static function build(
        RetrieveFullTracker $tracker_factory,
        UserStory $user_story,
    ): ?self {
        $tracker = $tracker_factory->getNonNullTracker($user_story->tracker_identifier);

        $feature_representation = null;
        if ($user_story->feature) {
            $feature_representation = MinimalFeatureRepresentation::fromFeature($user_story->feature);
        }

        return new self(
            $user_story->user_story_identifier->getId(),
            $user_story->uri,
            $user_story->cross_ref,
            $user_story->title,
            $user_story->is_open,
            new ProjectReference($tracker->getProject()),
            MinimalTrackerRepresentation::build($tracker),
            $user_story->background_color->getBackgroundColorName(),
            $feature_representation
        );
    }
}
