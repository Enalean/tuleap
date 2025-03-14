<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\FieldSpecificProperties;

use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ComputedFieldSpecificPropertiesDAOTest extends TestIntegrationTestCase
{
    private ComputedFieldSpecificPropertiesDAO $dao;
    private int $computed_field_id;
    private int $duplicate_field_id;

    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);
        $this->dao       = new ComputedFieldSpecificPropertiesDAO();

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();
        $tracker    = $tracker_builder->buildTracker($project_id, 'MyTracker');

        $this->computed_field_id  = $tracker_builder->buildComputedField($tracker->getId(), 'computed_name');
        $this->duplicate_field_id = $tracker_builder->buildComputedField($tracker->getId(), 'computed_name');
    }

    public function testDefaultProperties(): void
    {
        $properties = $this->dao->searchByFieldId($this->computed_field_id);
        self::assertSame(['field_id' => $this->computed_field_id, 'default_value' => null, 'target_field_name' => null], $properties);

        $this->dao->saveSpecificProperties($this->computed_field_id, []);
        $properties = $this->dao->searchByFieldId($this->computed_field_id);
        self::assertSame(['field_id' => $this->computed_field_id, 'default_value' => null, 'target_field_name' => ''], $properties);

        $this->dao->deleteFieldProperties($this->computed_field_id);

        $properties = $this->dao->searchByFieldId($this->computed_field_id);
        self::assertNull($properties);
    }

    public function testManualProperties(): void
    {
        $properties = $this->dao->searchByFieldId($this->computed_field_id);
        self::assertSame(['field_id' => $this->computed_field_id, 'default_value' => null, 'target_field_name' => null], $properties);

        $this->dao->saveSpecificProperties($this->computed_field_id, ['target_field_name' => 'target_name', 'default_value' => 22]);
        $properties = $this->dao->searchByFieldId($this->computed_field_id);
        self::assertSame(['field_id' => $this->computed_field_id, 'default_value' => 22.0, 'target_field_name' => 'target_name'], $properties);

        $this->dao->duplicate($this->computed_field_id, $this->duplicate_field_id);
        $properties = $this->dao->searchByFieldId($this->duplicate_field_id);
        self::assertSame(['field_id' => $this->duplicate_field_id, 'default_value' => null, 'target_field_name' => 'target_name'], $properties);
    }
}
