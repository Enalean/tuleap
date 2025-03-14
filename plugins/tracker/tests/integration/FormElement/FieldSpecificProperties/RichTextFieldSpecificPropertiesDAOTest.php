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
final class RichTextFieldSpecificPropertiesDAOTest extends TestIntegrationTestCase
{
    private RichTextFieldSpecificPropertiesDAO $dao;
    private int $rich_text_field_id;

    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $this->dao  = new RichTextFieldSpecificPropertiesDAO();
        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();
        $tracker    = $tracker_builder->buildTracker($project_id, 'MyTracker');

        $this->rich_text_field_id = $tracker_builder->buildStaticRichTextField($tracker->getId(), 'rich_text_name');
    }

    public function testDefaultProperties(): void
    {
        $properties = $this->dao->searchByFieldId($this->rich_text_field_id);
        self::assertNull($properties);

        $this->dao->saveSpecificProperties($this->rich_text_field_id, []);
        $properties = $this->dao->searchByFieldId($this->rich_text_field_id);

        self::assertNull($properties);
    }

    public function testManualProperties(): void
    {
        $properties = $this->dao->searchByFieldId($this->rich_text_field_id);
        self::assertNull($properties);

        $this->dao->saveSpecificProperties($this->rich_text_field_id, ['static_value' => 'My value']);
        $properties = $this->dao->searchByFieldId($this->rich_text_field_id);
        self::assertSame(['field_id' => $this->rich_text_field_id, 'static_value' => 'My value'], $properties);

        $this->dao->deleteFieldProperties($this->rich_text_field_id);

        $properties = $this->dao->searchByFieldId($this->rich_text_field_id);
        self::assertNull($properties);
    }
}
