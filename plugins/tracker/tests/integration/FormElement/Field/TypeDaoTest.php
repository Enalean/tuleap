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

namespace Tuleap\Tracker\FormElement\Field;

use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TypeDaoTest extends TestIntegrationTestCase
{
    private TypeDao $type_dao;

    #[\Override]
    protected function setUp(): void
    {
        $this->type_dao = new TypeDao();
    }

    public function testCanRetrieveUsedArtifactLinks(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();

        $tracker    = $tracker_builder->buildTracker($project_id, 'tracker');
        $tracker_id = $tracker->getId();

        $this->type_dao->create('example1', 'forward_example1', 'reverse_example1');
        $this->type_dao->create('example2', 'forward_example2', 'reverse_example2');

        $link_field = $tracker_builder->buildArtifactLinkField($tracker_id);

        $artifact_1 = $tracker_builder->buildArtifact($tracker_id);
        $artifact_2 = $tracker_builder->buildArtifact($tracker_id);
        $artifact_3 = $tracker_builder->buildArtifact($tracker_id);

        $changeset_artifact_1 = $tracker_builder->buildLastChangeset($artifact_1);
        $changeset_artifact_2 = $tracker_builder->buildLastChangeset($artifact_2);

        $tracker_builder->buildArtifactLinkValue(
            $changeset_artifact_1,
            $link_field,
            $artifact_2,
            'example1',
        );
        $tracker_builder->buildArtifactLinkValue(
            $changeset_artifact_1,
            $link_field,
            $artifact_3,
            'example2',
        );
        $tracker_builder->buildArtifactLinkValue(
            $changeset_artifact_2,
            $link_field,
            $artifact_3,
            'example1',
        );


        self::assertTrue($this->type_dao->isOrHasBeenUsed('example1'));
        self::assertFalse($this->type_dao->isOrHasBeenUsed('notused'));
        self::assertEqualsCanonicalizing(
            [['nature' => 'example1'], ['nature' => 'example2']],
            $this->type_dao->searchAllUsedTypesByProject($project_id)
        );
        self::assertEqualsCanonicalizing(
            [
                [
                    'nature' => 'example1',
                    'forward_label' => 'forward_example1',
                    'reverse_label' => 'reverse_example1',
                ],
                [
                    'nature' => 'example2',
                    'forward_label' => 'forward_example2',
                    'reverse_label' => 'reverse_example2',
                ],
            ],
            $this->type_dao->searchAllCurrentlyUsedTypesByTrackerID($tracker_id)
        );
    }
}
