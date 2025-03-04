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
final class ListFieldSpecificPropertiesDAOTest extends TestIntegrationTestCase
{
    private ListFieldSpecificPropertiesDAO $dao;
    private int $list_field_id;
    private int $duplicate_field_id;

    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $this->dao  = new ListFieldSpecificPropertiesDAO();
        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();
        $user       = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $user->getId(), $project_id);

        $tracker = $tracker_builder->buildTracker($project_id, 'MyTracker');
        $tracker_builder->setViewPermissionOnTracker($tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $this->list_field_id      = $tracker_builder->buildStaticListField($tracker->getId(), 'multi_name', 'msb');
        $this->duplicate_field_id = $tracker_builder->buildStaticListField($tracker->getId(), 'multi_name', 'msb');
    }

    public function testDefaultProperties(): void
    {
        $properties = $this->dao->searchBindByFieldId($this->list_field_id)->unwrapOr(null);
        self::assertSame('static', $properties);

        $this->dao->saveBindForFieldId($this->list_field_id, 'users');
        $properties = $this->dao->searchBindByFieldId($this->list_field_id)->unwrapOr(null);
        self::assertSame('users', $properties);

        $this->dao->duplicate($this->list_field_id, $this->duplicate_field_id);
        $properties = $this->dao->searchBindByFieldId($this->duplicate_field_id)->unwrapOr(null);
        self::assertSame('users', $properties);

        $this->dao->deleteFieldProperties($this->list_field_id);

        self::assertTrue($this->dao->searchBindByFieldId($this->list_field_id)->isNothing());
    }
}
