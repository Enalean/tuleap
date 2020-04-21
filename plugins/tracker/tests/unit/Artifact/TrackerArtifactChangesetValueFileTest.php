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

namespace Tuleap\Tracker\Artifact\Changeset;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_File;
use Tuleap\GlobalLanguageMock;

class TrackerArtifactChangesetValueFileTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var Tracker_Artifact_Changeset|\Mockery\MockInterface|Tracker_Artifact_Changeset
     */
    private $changeset;

    /**
     * @var Tracker_Artifact|\Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact;

    public function setUp(): void
    {
        parent::setUp();

        $this->artifact  = \Mockery::mock(\Tracker_Artifact::class);
        $this->changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $this->changeset->shouldReceive('getArtifact')->andReturn($this->artifact);
        $this->artifact->shouldReceive('getLastChangeset')->andReturn($this->changeset);
    }

    public function testNoDiff()
    {
        $info = \Mockery::mock(\Tracker_FileInfo::class);
        $info->shouldReceive('getFilename')->andReturns('Screenshot.png');
        $info->shouldReceive('getId')->andReturns(111);
        $field = \Mockery::mock(\Tracker_FormElement_Field_File::class);

        $file_1 = new Tracker_Artifact_ChangesetValue_File(111, $this->changeset, $field, false, array($info));
        $file_2 = new Tracker_Artifact_ChangesetValue_File(111, $this->changeset, $field, false, array($info));

        $this->assertFalse($file_1->diff($file_2));
        $this->assertFalse($file_2->diff($file_1));
    }

    public function testDiff()
    {
        $info = \Mockery::mock(\Tracker_FileInfo::class);
        $info->shouldReceive('__toString')->andReturns('#1 Screenshot.png');
        $info->shouldReceive('getFilename')->andReturns('Screenshot.png');
        $info->shouldReceive('getId')->andReturns(111);

        $field = \Mockery::mock(\Tracker_FormElement_Field_File::class);

        $file_1 = new Tracker_Artifact_ChangesetValue_File(111, $this->changeset, $field, false, array());
        $file_2 = new Tracker_Artifact_ChangesetValue_File(111, $this->changeset, $field, false, array($info));

        $this->changeset->shouldReceive('getValue')->andReturns($file_2);

        $GLOBALS['Language']->shouldReceive('getText')->with('plugin_tracker_artifact', 'added')->andReturns('added');
        $GLOBALS['Language']->shouldReceive('getText')
                            ->with('plugin_tracker_artifact', 'removed')
                            ->andReturns('removed');

        $this->assertEquals('Screenshot.png removed', $file_1->diff($file_2, 'text'));
        $this->assertEquals('Screenshot.png added', $file_2->diff($file_1, 'text'));
    }

    public function testDiffWithLotOfFiles()
    {
        $info1 = \Mockery::mock(\Tracker_FileInfo::class);
        $info1->shouldReceive('__toString')->andReturns('#1 Screenshot1.png');
        $info1->shouldReceive('getFilename')->andReturns('Screenshot1.png');
        $info1->shouldReceive('getId')->andReturns(1);

        $info2 = \Mockery::mock(\Tracker_FileInfo::class);
        $info2->shouldReceive('__toString')->andReturns('#2 Screenshot2.png');
        $info2->shouldReceive('getFilename')->andReturns('Screenshot2.png');
        $info2->shouldReceive('getId')->andReturns(2);

        $info3 = \Mockery::mock(\Tracker_FileInfo::class);
        $info3->shouldReceive('__toString')->andReturns('#3 Screenshot3.png');
        $info3->shouldReceive('getFilename')->andReturns('Screenshot3.png');
        $info3->shouldReceive('getId')->andReturns(3);

        $info4 = \Mockery::mock(\Tracker_FileInfo::class);
        $info4->shouldReceive('__toString')->andReturns('#4 Screenshot4.png');
        $info4->shouldReceive('getFilename')->andReturns('Screenshot4.png');
        $info3->shouldReceive('getId')->andReturns(4);

        $field = \Mockery::mock(\Tracker_FormElement_Field_File::class);

        $file_1 = new Tracker_Artifact_ChangesetValue_File(
            111,
            $this->changeset,
            $field,
            false,
            array($info1, $info3, $info4)
        );
        $file_2 = new Tracker_Artifact_ChangesetValue_File(111, $this->changeset, $field, false, array($info1, $info2));

        $this->changeset->shouldReceive('getValue')->andReturns($file_2);

        $GLOBALS['Language']->shouldReceive('getText')->with('plugin_tracker_artifact', 'added')->andReturns('added');
        $GLOBALS['Language']->shouldReceive('getText')
                            ->with('plugin_tracker_artifact', 'removed')
                            ->andReturns('removed');

        $this->assertEquals(
            'Screenshot2.png removed' . PHP_EOL . 'Screenshot3.png, Screenshot4.png added',
            $file_1->diff($file_2, 'text')
        );

        $this->assertEquals(
            'Screenshot3.png, Screenshot4.png removed' . PHP_EOL . 'Screenshot2.png added',
            $file_2->diff($file_1, 'text')
        );
    }
}
