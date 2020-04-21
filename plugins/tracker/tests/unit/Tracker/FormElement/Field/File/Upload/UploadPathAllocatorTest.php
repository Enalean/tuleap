<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\File\Upload;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElementFactory;
use Tuleap\Upload\FileBeingUploadedInformation;

class UploadPathAllocatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testTheSamePathIsAlwaysAllocatedForAGivenItemID(): void
    {
        $dao = \Mockery::mock(FileOngoingUploadDao::class);
        $dao->shouldReceive('searchFileOngoingUploadById')
            ->with(1)
            ->andReturn(['field_id' => 42]);

        $field = \Mockery::mock(\Tracker_FormElement_Field_File::class);
        $field->shouldReceive('getRootPath')->andReturn('/var/tmp');

        $factory = \Mockery::mock(Tracker_FormElementFactory::class);
        $factory->shouldReceive('getFieldById')
            ->with(42)
            ->andReturn($field);
        $factory->shouldReceive('isFieldAFileField')
            ->with($field)
            ->andReturn(true);

        $allocator = new UploadPathAllocator($dao, $factory);

        $this->assertSame(
            $allocator->getPathForItemBeingUploaded(new FileBeingUploadedInformation(1, 'Filename', 123, 0)),
            $allocator->getPathForItemBeingUploaded(new FileBeingUploadedInformation(1, 'Filename', 123, 0))
        );
    }
}
