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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue_File;
use Tracker_FileInfo;
use Tracker_FormElement_Field_File;

class ArtifactFilesDeletorVisitorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact;

    /**
     * @var ArtifactFilesDeletorVisitor
     */
    private $visitor;

    protected function setUp(): void
    {
        $this->artifact = Mockery::mock(Tracker_Artifact::class);
        $this->visitor  = new ArtifactFilesDeletorVisitor($this->artifact);
    }

    public function testItDeleteFile(): void
    {
        $formElement = Mockery::mock(Tracker_FormElement_Field_File::class);

        $file1 = Mockery::mock(Tracker_FileInfo::class);
        $file1->shouldReceive('deleteFiles')->once();

        $file2 = Mockery::mock(Tracker_FileInfo::class);
        $file2->shouldReceive('deleteFiles')->once();

        $files = [$file1, $file2];

        $changeset_value_file = Mockery::mock(Tracker_Artifact_ChangesetValue_File::class);
        $changeset_value_file->shouldReceive('getFiles')->andReturn($files);

        $this->artifact->shouldReceive('getValue')->once()->andReturn($changeset_value_file);

        $this->visitor->visitFile($formElement);
    }
}
