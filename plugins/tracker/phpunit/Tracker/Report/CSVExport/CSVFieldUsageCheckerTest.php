<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Report\CSVExport;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CSVFieldUsageCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testUnusedFieldsAreNotExportedInCSV(): void
    {
        $field = \Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $field->shouldReceive('isUsed')->andReturnFalse();
        $this->assertFalse(CSVFieldUsageChecker::canFieldBeExportedToCSV($field));
    }

    public function testUserCantExportFieldHeCanNotReadInCSV(): void
    {
        $field = \Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $field->shouldReceive('isUsed')->andReturnTrue();
        $field->shouldReceive('userCanRead')->andReturnFalse();
        $this->assertFalse(CSVFieldUsageChecker::canFieldBeExportedToCSV($field));
    }


    public function testBurndownFieldIsNotExportedInCSV(): void
    {
        $field = \Mockery::mock(\Tracker_FormElement_Field_Burndown::class);
        $field->shouldReceive('isUsed')->andReturnTrue();
        $field->shouldReceive('userCanRead')->andReturnTrue();
        $this->assertFalse(CSVFieldUsageChecker::canFieldBeExportedToCSV($field));
    }

    public function testArtifactIdIsNotExportedInCSV(): void
    {
        $field = \Mockery::mock(\Tracker_FormElement_Field_ArtifactId::class);
        $field->shouldReceive('isUsed')->andReturnTrue();
        $field->shouldReceive('userCanRead')->andReturnTrue();
        $this->assertFalse(CSVFieldUsageChecker::canFieldBeExportedToCSV($field));
    }

    public function testPerTrackerIdFieldIsExportedInCSV(): void
    {
        $field = \Mockery::mock(\Tracker_FormElement_Field_PerTrackerArtifactId::class);
        $field->shouldReceive('isUsed')->andReturnTrue();
        $field->shouldReceive('userCanRead')->andReturnTrue();
        $this->assertTrue(CSVFieldUsageChecker::canFieldBeExportedToCSV($field));
    }
}
