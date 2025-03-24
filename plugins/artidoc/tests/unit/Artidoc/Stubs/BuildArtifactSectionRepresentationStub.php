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

namespace Tuleap\Artidoc\Stubs;

use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Level;
use Tuleap\Artidoc\REST\v1\ArtifactSectionRepresentation;
use Tuleap\Artidoc\REST\v1\BuildArtifactSectionRepresentation;
use Tuleap\Artidoc\REST\v1\RequiredArtifactInformation;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;

final class BuildArtifactSectionRepresentationStub implements BuildArtifactSectionRepresentation
{
    private function __construct()
    {
    }

    public static function instance(): self
    {
        return new self();
    }

    public function build(
        RequiredArtifactInformation $artifact_information,
        SectionIdentifier $section_identifier,
        Level $level,
        \PFUser $user,
    ): ArtifactSectionRepresentation {
        $can_user_edit_section = true;
        $attachments           = null;

        return new ArtifactSectionRepresentation(
            $section_identifier->toString(),
            $level->value,
            ArtifactReference::build($artifact_information->last_changeset->getArtifact()),
            $artifact_information->title,
            $artifact_information->description,
            $can_user_edit_section,
            $attachments,
            [],
        );
    }
}
