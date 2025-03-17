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

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinkFieldSpecificPropertiesDAOTest extends TestIntegrationTestCase
{
    private EasyDB $db;
    private ArtifactLinkFieldSpecificPropertiesDAO $dao;
    private int $artifact_link_field_id;

    protected function setUp(): void
    {
        $this->db        = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($this->db);
        $core_builder    = new CoreDatabaseBuilder($this->db);
        $this->dao       = new ArtifactLinkFieldSpecificPropertiesDAO();

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();
        $tracker    = $tracker_builder->buildTracker($project_id, 'MyTracker');

        $this->artifact_link_field_id = $tracker_builder->buildArtifactLinkField($tracker->getId());
    }

    public function testManualProperties(): void
    {
        $empty_properties = $this->dao->searchByFieldId($this->artifact_link_field_id);
        self::assertEmpty($empty_properties);

        $this->db->run(
            'INSERT INTO tracker_field_artifact_link(field_id, can_edit_reverse_links) VALUES(?, true)',
            $this->artifact_link_field_id,
        );

        $properties = $this->dao->searchByFieldId($this->artifact_link_field_id);
        self::assertSame(['field_id' => $this->artifact_link_field_id, 'can_edit_reverse_links' => 1], $properties);
    }
}
