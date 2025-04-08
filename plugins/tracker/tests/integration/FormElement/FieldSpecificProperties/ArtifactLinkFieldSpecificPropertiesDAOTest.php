<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
final class ArtifactLinkFieldSpecificPropertiesDAOTest extends TestIntegrationTestCase
{
    private ArtifactLinkFieldSpecificPropertiesDAO $dao;
    private int $artifact_link_field_id;
    private int $artifact_link_field_id_2;

    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);
        $this->dao       = new ArtifactLinkFieldSpecificPropertiesDAO();

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();
        $tracker    = $tracker_builder->buildTracker($project_id, 'MyTracker');

        $this->artifact_link_field_id   = $tracker_builder->buildArtifactLinkField($tracker->getId());
        $this->artifact_link_field_id_2 = $tracker_builder->buildArtifactLinkField($tracker->getId());
    }

    public function testSaveAndSearchProperties(): void
    {
        $empty_properties = $this->dao->searchByFieldId($this->artifact_link_field_id);
        self::assertEmpty($empty_properties);

        $this->dao->saveSpecificProperties($this->artifact_link_field_id, []);
        $properties = $this->dao->searchByFieldId($this->artifact_link_field_id);
        self::assertSame(['field_id' => $this->artifact_link_field_id, 'can_edit_reverse_links' => 0], $properties);

        $this->dao->saveSpecificProperties($this->artifact_link_field_id, ['can_edit_reverse_links' => 1]);
        $properties = $this->dao->searchByFieldId($this->artifact_link_field_id);
        self::assertSame(['field_id' => $this->artifact_link_field_id, 'can_edit_reverse_links' => 1], $properties);

        $this->dao->saveSpecificProperties($this->artifact_link_field_id, ['can_edit_reverse_links' => 0]);
        $properties = $this->dao->searchByFieldId($this->artifact_link_field_id);
        self::assertSame(['field_id' => $this->artifact_link_field_id, 'can_edit_reverse_links' => 0], $properties);
    }

    public function testDuplicateProperties(): void
    {
        $this->dao->saveSpecificProperties($this->artifact_link_field_id, ['can_edit_reverse_links' => 1]);
        $properties = $this->dao->searchByFieldId($this->artifact_link_field_id);
        self::assertSame(['field_id' => $this->artifact_link_field_id, 'can_edit_reverse_links' => 1], $properties);

        $this->dao->duplicate($this->artifact_link_field_id, $this->artifact_link_field_id_2);
        $properties = $this->dao->searchByFieldId($this->artifact_link_field_id_2);
        self::assertSame(['field_id' => $this->artifact_link_field_id_2, 'can_edit_reverse_links' => 1], $properties);
    }
}
