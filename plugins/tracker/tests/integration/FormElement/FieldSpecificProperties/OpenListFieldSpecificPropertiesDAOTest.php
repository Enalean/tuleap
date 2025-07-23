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
final class OpenListFieldSpecificPropertiesDAOTest extends TestIntegrationTestCase
{
    private OpenListSpecificPropertiesDAO $dao;
    private int $list_field_id;

    #[\Override]
    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);
        $this->dao       = new OpenListSpecificPropertiesDAO();

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();
        $tracker    = $tracker_builder->buildTracker($project_id, 'MyTracker');

        $this->list_field_id = $tracker_builder->buildStaticListField($tracker->getId(), 'list_name', 'tbl');
    }

    public function testDefaultProperties(): void
    {
        $properties = $this->dao->searchByFieldId($this->list_field_id);
        self::assertNull($properties);

        $this->dao->saveSpecificProperties($this->list_field_id, []);
        $properties = $this->dao->searchByFieldId($this->list_field_id);

        self::assertSame(['field_id' => $this->list_field_id, 'hint' => ''], $properties);

        $this->dao->deleteFieldProperties($this->list_field_id);

        $properties = $this->dao->searchByFieldId($this->list_field_id);
        self::assertNull($properties);
    }

    public function testManualProperties(): void
    {
        $properties = $this->dao->searchByFieldId($this->list_field_id);
        self::assertNull($properties);

        $this->dao->saveSpecificProperties($this->list_field_id, ['hint' => 'My hint']);
        $properties = $this->dao->searchByFieldId($this->list_field_id);
        self::assertSame(['field_id' => $this->list_field_id, 'hint' => 'My hint'], $properties);
    }
}
