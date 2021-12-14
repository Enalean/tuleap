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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\BackgroundColor;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;

/**
 * @psalm-immutable
 */
final class FeatureRepresentation extends ElementRepresentation
{
    /**
     * @var MinimalTrackerRepresentation
     */
    public $tracker;
    /**
     * @var string
     */
    public $background_color;
    /**
     * @var bool
     */
    public $has_user_story_planned;
    /**
     * @var bool
     */
    public $has_user_story_linked;

    public function __construct(
        int $artifact_id,
        ?string $artifact_title,
        string $artifact_xref,
        string $artifact_url,
        MinimalTrackerRepresentation $minimal_tracker_representation,
        BackgroundColor $background_color,
        bool $has_user_story_planned,
        bool $has_user_story_linked,
    ) {
        parent::__construct($artifact_id, $artifact_url, $artifact_xref, $artifact_title);
        $this->tracker                = $minimal_tracker_representation;
        $this->background_color       = $background_color->getBackgroundColorName();
        $this->has_user_story_planned = $has_user_story_planned;
        $this->has_user_story_linked  = $has_user_story_linked;
    }
}
