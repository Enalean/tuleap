<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Fields\RetrieveFullArtifactLinkField;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;

final class RetrieveFullArtifactLinkFieldStub implements RetrieveFullArtifactLinkField
{
    private function __construct(private ?\Tracker_FormElement_Field_ArtifactLink $artifact_link)
    {
    }

    public static function withField(\Tracker_FormElement_Field_ArtifactLink $artifact_link): self
    {
        return new self($artifact_link);
    }

    public static function withNoField(): self
    {
        return new self(null);
    }

    public function getArtifactLinkField(
        TrackerIdentifier $tracker_identifier,
    ): ?\Tracker_FormElement_Field_ArtifactLink {
        return $this->artifact_link;
    }
}
