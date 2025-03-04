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

namespace Tuleap\Tracker\Artifact\FormElement\FieldSpecificProperties;

use ProjectUGroup;
use Tracker;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StringFieldSpecificPropertiesDAOTest extends TestIntegrationTestCase
{
    private StringFieldSpecificPropertiesDAO $dao;
    private int $string_field_id;
    private int $duplicate_field_id;

    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $this->dao  = new StringFieldSpecificPropertiesDAO();
        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();
        $user       = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $user->getId(), $project_id);

        $tracker = $tracker_builder->buildTracker($project_id, 'MyTracker');
        $tracker_builder->setViewPermissionOnTracker($tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $this->string_field_id    = $tracker_builder->buildStringField($tracker->getId(), 'string_name');
        $this->duplicate_field_id = $tracker_builder->buildStringField($tracker->getId(), 'string_name');
    }

    public function testDefaultProperties(): void
    {
        $properties = $this->dao->searchByFieldId($this->string_field_id);
        self::assertEquals(['field_id' => $this->string_field_id, 'maxchars' => 0, 'size' => 0, 'default_value' => null], $properties);

        $this->dao->saveSpecificProperties($this->string_field_id, []);
        $properties = $this->dao->searchByFieldId($this->string_field_id);

        self::assertEquals(['field_id' => $this->string_field_id, 'maxchars' => 0, 'size' => 30, 'default_value' => ''], $properties);

        $this->dao->deleteFieldProperties($this->string_field_id);

        $properties = $this->dao->searchByFieldId($this->string_field_id);
        self::assertNull($properties);
    }

    public function testManualProperties(): void
    {
        $properties = $this->dao->searchByFieldId($this->string_field_id);
        self::assertEquals(['field_id' => $this->string_field_id, 'maxchars' => 0, 'size' => 0, 'default_value' => null], $properties);

        $this->dao->saveSpecificProperties($this->string_field_id, ['maxchars' => 22, 'size' => 40, 'default_value' => 'default string value']);
        $properties = $this->dao->searchByFieldId($this->string_field_id);
        self::assertEquals(['field_id' => $this->string_field_id, 'maxchars' => 22, 'size' => 40, 'default_value' => 'default string value'], $properties);

        $this->dao->duplicate($this->string_field_id, $this->duplicate_field_id);
        $properties = $this->dao->searchByFieldId($this->duplicate_field_id);
        self::assertEquals(['field_id' => $this->duplicate_field_id, 'maxchars' => 22, 'size' => 40, 'default_value' => 'default string value'], $properties);
    }
}
