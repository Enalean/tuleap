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

namespace Tuleap\Artidoc\REST\v1;

use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFileFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;
use Tuleap\Tracker\REST\Artifact\ArtifactTextFieldValueRepresentation;

/**
 * @psalm-immutable
 */
final readonly class ArtidocSectionRepresentation
{
    /**
     * @psalm-param ArtifactFieldValueFullRepresentation|ArtifactTextFieldValueRepresentation $title
     */
    public function __construct(
        public string $id,
        public ArtifactReference $artifact,
        public mixed $title,
        public ArtifactTextFieldValueRepresentation $description,
        public bool $can_user_edit_section,
        public ?ArtifactFieldValueFileFullRepresentation $attachments,
    ) {
    }

    public static function fromRepresentationWithId(self $representation, SectionIdentifier $id): self
    {
        return new self(
            $id->toString(),
            $representation->artifact,
            $representation->title,
            $representation->description,
            $representation->can_user_edit_section,
            $representation->attachments,
        );
    }
}
