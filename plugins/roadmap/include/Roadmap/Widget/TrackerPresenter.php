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

use Tracker;

/**
 * @psalm-immutable
 */
class TrackerPresenter
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $title;
    /**
     * @var bool
     */
    public $selected;

    public function __construct(int $id, string $title, bool $selected)
    {
        $this->id       = $id;
        $this->title    = $title;
        $this->selected = $selected;
    }

    public static function buildFromPreferencePresenter(Tracker $tracker, ?int $selected_tracker_id): self
    {
        $tracker_id = $tracker->getId();
        $selected   = false;
        if ($selected_tracker_id === $tracker_id) {
            $selected = true;
        }

        return new self(
            $tracker_id,
            $tracker->getName(),
            $selected
        );
    }
}
