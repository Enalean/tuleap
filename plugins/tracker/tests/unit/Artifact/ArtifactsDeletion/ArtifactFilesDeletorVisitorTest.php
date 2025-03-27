<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ArtifactsDeletion;

use Tracker_FileInfo;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueFileTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FileFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactFilesDeletorVisitorTest extends TestCase
{
    private Artifact $artifact;
    private ArtifactFilesDeletorVisitor $visitor;

    protected function setUp(): void
    {
        $this->artifact = ArtifactTestBuilder::anArtifact(1)->build();
        $this->visitor  = new ArtifactFilesDeletorVisitor($this->artifact);
    }

    public function testItDeleteFile(): void
    {
        $field = FileFieldBuilder::aFileField(25)->build();

        $file1 = $this->createMock(Tracker_FileInfo::class);
        $file1->expects($this->once())->method('deleteFiles');

        $file2 = $this->createMock(Tracker_FileInfo::class);
        $file2->expects($this->once())->method('deleteFiles');

        $files = [$file1, $file2];

        $changeset = ChangesetTestBuilder::aChangeset(456)->build();
        $changeset->setFieldValue($field, ChangesetValueFileTestBuilder::aValue(1, $changeset, $field)->withFiles($files)->build());

        $this->artifact->setLastChangeset($changeset);

        $this->visitor->visitFile($field);
    }
}
