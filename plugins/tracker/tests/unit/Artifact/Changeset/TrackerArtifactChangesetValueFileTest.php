<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, Enalean, 2001-present. All rights reserved
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

namespace Tuleap\Tracker\Artifact\Changeset;

use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_File;
use Tracker_FileInfo;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FileFieldBuilder;

final class TrackerArtifactChangesetValueFileTest extends TestCase
{
    private Tracker_Artifact_Changeset $changeset;

    public function setUp(): void
    {
        $this->changeset           = ChangesetTestBuilder::aChangeset(15)->build();
        $artifact                  = ArtifactTestBuilder::anArtifact(354)->withChangesets($this->changeset)->build();
        $this->changeset->artifact = $artifact;
    }

    public function testNoDiff(): void
    {
        $info = $this->createMock(Tracker_FileInfo::class);
        $info->method('getFilename')->willReturn('Screenshot.png');
        $info->method('getId')->willReturn(111);
        $field = FileFieldBuilder::aFileField(45)->build();

        $file_1 = new Tracker_Artifact_ChangesetValue_File(111, $this->changeset, $field, false, [$info]);
        $file_2 = new Tracker_Artifact_ChangesetValue_File(111, $this->changeset, $field, false, [$info]);

        self::assertFalse($file_1->diff($file_2));
        self::assertFalse($file_2->diff($file_1));
    }

    public function testDiff(): void
    {
        $info = $this->createMock(Tracker_FileInfo::class);
        $info->method('__toString')->willReturn('#1 Screenshot.png');
        $info->method('getFilename')->willReturn('Screenshot.png');
        $info->method('getId')->willReturn(111);

        $field = FileFieldBuilder::aFileField(45)->build();

        $file_1 = new Tracker_Artifact_ChangesetValue_File(111, $this->changeset, $field, false, []);
        $file_2 = new Tracker_Artifact_ChangesetValue_File(111, $this->changeset, $field, false, [$info]);

        $this->changeset->setFieldValue($field, $file_2);

        self::assertEquals('Screenshot.png removed', $file_1->diff($file_2, 'text'));
        self::assertEquals('Screenshot.png added', $file_2->diff($file_1, 'text'));
    }

    public function testDiffWithLotOfFiles(): void
    {
        $info1 = $this->createMock(Tracker_FileInfo::class);
        $info1->method('__toString')->willReturn('#1 Screenshot1.png');
        $info1->method('getFilename')->willReturn('Screenshot1.png');
        $info1->method('getId')->willReturn(1);

        $info2 = $this->createMock(Tracker_FileInfo::class);
        $info2->method('__toString')->willReturn('#2 Screenshot2.png');
        $info2->method('getFilename')->willReturn('Screenshot2.png');
        $info2->method('getId')->willReturn(2);

        $info3 = $this->createMock(Tracker_FileInfo::class);
        $info3->method('__toString')->willReturn('#3 Screenshot3.png');
        $info3->method('getFilename')->willReturn('Screenshot3.png');
        $info3->method('getId')->willReturn(3);

        $info4 = $this->createMock(Tracker_FileInfo::class);
        $info4->method('__toString')->willReturn('#4 Screenshot4.png');
        $info4->method('getFilename')->willReturn('Screenshot4.png');
        $info3->method('getId')->willReturn(4);

        $field = FileFieldBuilder::aFileField(45)->build();

        $file_1 = new Tracker_Artifact_ChangesetValue_File(
            111,
            $this->changeset,
            $field,
            false,
            [$info1, $info3, $info4]
        );
        $file_2 = new Tracker_Artifact_ChangesetValue_File(111, $this->changeset, $field, false, [$info1, $info2]);

        $this->changeset->setFieldValue($field, $file_2);

        self::assertEquals(
            'Screenshot2.png removed' . PHP_EOL . 'Screenshot3.png, Screenshot4.png added',
            $file_1->diff($file_2, 'text')
        );

        self::assertEquals(
            'Screenshot3.png, Screenshot4.png removed' . PHP_EOL . 'Screenshot2.png added',
            $file_2->diff($file_1, 'text')
        );
    }
}
