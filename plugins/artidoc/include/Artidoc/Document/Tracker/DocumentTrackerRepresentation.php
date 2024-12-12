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
use Tracker_FormElementFactory;
use Tracker_Semantic_Description;
use Tracker_Semantic_Title;
use Tuleap\Project\REST\MinimalProjectRepresentation;
use Tuleap\Tracker\Artifact\GetFileUploadData;

/**
 * @psalm-immutable
 */
final readonly class DocumentTrackerRepresentation
{
    private function __construct(
        public int $id,
        public string $label,
        public string $color,
        public string $item_name,
        public ?DocumentTrackerFieldStringRepresentation $title,
        public ?DocumentTrackerFieldTextRepresentation $description,
        public ?DocumentTrackerFieldFileRepresentation $file,
        public MinimalProjectRepresentation $project,
    ) {
    }

    public static function fromTracker(GetFileUploadData $file_upload_provider, Tracker $tracker, PFUser $user): self
    {
        $title_field = Tracker_Semantic_Title::load($tracker)->getField();
        $title       = $title_field && $title_field instanceof Tracker_FormElement_Field_String && $title_field->userCanSubmit($user)
            ? new DocumentTrackerFieldStringRepresentation($title_field->getId(), $title_field->getLabel(), Tracker_FormElementFactory::instance()->getType($title_field), $title_field->getDefaultRESTValue())
            : null;

        $description_field = Tracker_Semantic_Description::load($tracker)->getField();
        $description       = $description_field && $description_field->userCanSubmit($user)
            ? new DocumentTrackerFieldTextRepresentation($description_field->getId(), $description_field->getLabel(), Tracker_FormElementFactory::instance()->getType($description_field), $description_field->getDefaultRESTValue())
            : null;

        $file_upload_data = $file_upload_provider->getFileUploadData($tracker, null, $user);

        $file_field = $file_upload_data?->getField();
        $file       = $file_field && $file_field->userCanSubmit($user)
            ? new DocumentTrackerFieldFileRepresentation($file_field->getId(), $file_field->getLabel(), Tracker_FormElementFactory::instance()->getType($file_field))
            : null;

        $project = new MinimalProjectRepresentation($tracker->getProject());

        return new self(
            $tracker->getId(),
            $tracker->getName(),
            $tracker->getColor()->getName(),
            $tracker->getItemName(),
            $title,
            $description,
            $file,
            $project,
        );
    }
}
