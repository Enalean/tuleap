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

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CSVFieldUsageCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->user = UserTestBuilder::buildWithDefaults();

        $manager = $this->createMock(\UserManager::class);
        $manager->method('getCurrentUser')->willReturn($this->user);
        \UserManager::setInstance($manager);
    }

    protected function tearDown(): void
    {
        \UserManager::clearInstance();
    }

    public function testUnusedFieldsAreNotExportedInCSV(): void
    {
        $field = IntegerFieldBuilder::anIntField(1)->unused()->build();
        $this->assertFalse(CSVFieldUsageChecker::canFieldBeExportedToCSV($field));
    }

    public function testUserCantExportFieldHeCanNotReadInCSV(): void
    {
        $field = IntegerFieldBuilder::anIntField(1)->withReadPermission($this->user, false)->build();
        $this->assertFalse(CSVFieldUsageChecker::canFieldBeExportedToCSV($field));
    }

    public function testBurndownFieldIsNotExportedInCSV(): void
    {
        $field = $this->createMock(\Tracker_FormElement_Field_Burndown::class);
        $field->method('isUsed')->willReturn(true);
        $field->method('userCanRead')->willReturn(true);
        $this->assertFalse(CSVFieldUsageChecker::canFieldBeExportedToCSV($field));
    }

    public function testArtifactIdIsNotExportedInCSV(): void
    {
        $field = $this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactId\ArtifactIdField::class);
        $field->method('isUsed')->willReturn(true);
        $field->method('userCanRead')->willReturn(true);
        $this->assertFalse(CSVFieldUsageChecker::canFieldBeExportedToCSV($field));
    }

    public function testPerTrackerIdFieldIsExportedInCSV(): void
    {
        $field = $this->createMock(\Tracker_FormElement_Field_PerTrackerArtifactId::class);
        $field->method('isUsed')->willReturn(true);
        $field->method('userCanRead')->willReturn(true);
        $this->assertTrue(CSVFieldUsageChecker::canFieldBeExportedToCSV($field));
    }
}
