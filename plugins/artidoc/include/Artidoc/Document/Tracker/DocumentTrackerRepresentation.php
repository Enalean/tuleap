<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Tracker;

use PFUser;
use Tracker;
use Tracker_FormElement_Field_String;
use Tracker_Semantic_Description;
use Tracker_Semantic_Title;

/**
 * @psalm-immutable
 */
final readonly class DocumentTrackerRepresentation
{
    private function __construct(
        public int $id,
        public string $label,
        public ?DocumentTrackerFieldRepresentation $title,
        public ?DocumentTrackerFieldRepresentation $description,
    ) {
    }

    public static function fromTracker(Tracker $tracker, PFUser $user): self
    {
        $title_field = Tracker_Semantic_Title::load($tracker)->getField();
        $title       = $title_field && $title_field instanceof Tracker_FormElement_Field_String && $title_field->userCanSubmit($user)
            ? new DocumentTrackerFieldRepresentation($title_field->getId())
            : null;

        $description_field = Tracker_Semantic_Description::load($tracker)->getField();
        $description       = $description_field && $description_field->userCanSubmit($user)
            ? new DocumentTrackerFieldRepresentation($description_field->getId())
            : null;

        return new self($tracker->getId(), $tracker->getName(), $title, $description);
    }
}
