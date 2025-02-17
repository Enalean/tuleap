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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueTextRepresentation;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;

final class RequiredArtifactInformationTestBuilder
{
    private function __construct(private Artifact $artifact)
    {
    }

    public static function fromArtifact(Artifact $artifact): self
    {
        return new self($artifact);
    }

    public function build(): RequiredArtifactInformation
    {
        $description = new ArtifactFieldValueTextRepresentation(
            1001,
            'text',
            'Details',
            'dolor sit amet',
            'dolor sit amet',
            'html',
        );

        return new RequiredArtifactInformation(
            ChangesetTestBuilder::aChangeset(1)->ofArtifact($this->artifact)->build(),
            TextFieldBuilder::aTextField(1001)->build(),
            'Lorem ipsum',
            TextFieldBuilder::aTextField(1002)->build(),
            $description,
        );
    }
}
