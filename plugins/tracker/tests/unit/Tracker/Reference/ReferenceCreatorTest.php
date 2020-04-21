<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

require_once __DIR__ . '/../../bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ReferenceCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ReferenceCreator
     */
    private $creator;

    public function setUp(): void
    {
        parent::setUp();

        $this->service_manager = \Mockery::spy(\ServiceManager::class);
        $this->tv3             = \Mockery::spy(\TrackerV3::class);
        $this->reference_dao   = \Mockery::spy(\ReferenceDao::class);

        $this->creator = new ReferenceCreator(
            $this->service_manager,
            $this->tv3,
            $this->reference_dao
        );

        $this->project = \Mockery::spy(\Project::class);
    }

    public function testItDoesNotCreateFromLegacyReferenceIsTV3AreNotAvailable()
    {
        $this->tv3->shouldReceive('available')->andReturns(false);

        $this->reference_dao->shouldReceive('getSystemReferenceByNatureAndKeyword')->never();
        $this->reference_dao->shouldReceive('create_ref_group')->never();

        $this->creator->insertArtifactsReferencesFromLegacy($this->project);
    }

    public function testItCreatesArtAndArtifactsReferencesFromLegacyArtifactReferences()
    {
        $this->project->shouldReceive([
            'usesService' => true,
            'getID'       => 101
        ]);

        $this->tv3->shouldReceive('available')->andReturns(true);

        $service = \Mockery::spy(\Service::class);
        $service->shouldReceive('getShortName')->andReturns('plugin_tracker');
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->project)->andReturns(
            [$service]
        );

        $this->reference_dao->shouldReceive('getSystemReferenceByNatureAndKeyword')
            ->with('art', 'artifact')->once()->andReturns(['id' => 1]);
        $this->reference_dao->shouldReceive('getSystemReferenceByNatureAndKeyword')
            ->with('artifact', 'artifact')->once()->andReturns(['id' => 2]);

        $this->reference_dao->shouldReceive('create_ref_group')->with(1, true, 101)->once();
        $this->reference_dao->shouldReceive('create_ref_group')->with(2, true, 101)->once();

        $this->creator->insertArtifactsReferencesFromLegacy($this->project);
    }
}
