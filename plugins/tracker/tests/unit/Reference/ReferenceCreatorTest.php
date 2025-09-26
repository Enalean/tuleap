<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Reference;

use PHPUnit\Framework\MockObject\MockObject;
use ServiceManager;
use trackerPlugin;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ReferenceCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ReferenceCreator $creator;
    private ServiceManager&MockObject $service_manager;
    private \TrackerV3&MockObject $tv3;
    private \ReferenceDao&MockObject $reference_dao;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->service_manager = $this->createMock(\ServiceManager::class);
        $this->tv3             = $this->createMock(\TrackerV3::class);
        $this->reference_dao   = $this->createMock(\ReferenceDao::class);

        $this->creator = new ReferenceCreator(
            $this->service_manager,
            $this->tv3,
            $this->reference_dao
        );
    }

    public function testItDoesNotCreateFromLegacyReferenceIsTV3AreNotAvailable(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->tv3->method('available')->willReturn(false);

        $this->reference_dao->expects($this->never())->method('getSystemReferenceByNatureAndKeyword');
        $this->reference_dao->expects($this->never())->method('create_ref_group');

        $this->creator->insertArtifactsReferencesFromLegacy($project);
    }

    public function testItCreatesArtAndArtifactsReferencesFromLegacyArtifactReferences(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->withUsedService(trackerPlugin::SERVICE_SHORTNAME)
            ->build();

        $this->tv3->method('available')->willReturn(true);

        $service = $this->createMock(\Service::class);
        $service->method('getShortName')->willReturn('plugin_tracker');
        $this->service_manager->method('getListOfAllowedServicesForProject')->with($project)->willReturn(
            [$service]
        );

        $this->reference_dao
            ->expects($this->exactly(2))
            ->method('getSystemReferenceByNatureAndKeyword')
            ->willReturnCallback(static fn (string $keyword, string $nature) => match (true) {
                $keyword === 'art' && $nature === 'artifact' => ['id' => 1],
                $keyword === 'artifact' && $nature === 'artifact' => ['id' => 2],
            });

        $this->reference_dao->expects($this->exactly(2))
            ->method('create_ref_group')
            ->willReturnCallback(static fn (int $refid, bool $is_active, mixed $group_id) => match (true) {
                ($refid === 1 || $refid === 2) && $is_active && (int) $group_id === 101 => true,
            });

        $this->creator->insertArtifactsReferencesFromLegacy($project);
    }
}
