<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST;

use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker;
use Tracker_ArtifactFactory;
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\Campaign\Execution\DefinitionForExecutionRetriever;
use Tuleap\TestManagement\Campaign\Execution\DefinitionNotFoundException;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\REST\v1\ExecutionWithAutomatedTestData;
use Tuleap\TestManagement\REST\v1\ExecutionWithAutomatedTestDataProvider;
use Tuleap\TestManagement\REST\v1\ListOfExecutionsWithAutomatedTestDataRetriever;
use Tuleap\Tracker\Artifact\Artifact;

class ListOfExecutionsWithAutomatedTestDataRetrieverTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Config
     */
    private $config;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactDao
     */
    private $artifact_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|DefinitionForExecutionRetriever
     */
    private $definition_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExecutionWithAutomatedTestDataProvider
     */
    private $execution_with_automated_data_provider;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact
     */
    private $artifact;
    /**
     * @var ListOfExecutionsWithAutomatedTestDataRetriever
     */
    private $list_of_executions_with_automated_test_data_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $project = Mockery::mock(Project::class);

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getProject')->andReturn($project);

        $this->user = Mockery::mock(PFUser::class);

        $this->artifact = Mockery::mock(Artifact::class);
        $this->artifact->shouldReceive('getId')->andReturn(102);
        $this->artifact->shouldReceive('getTracker')->andReturn($tracker);

        $this->config = Mockery::mock(Config::class);

        $this->artifact_dao = Mockery::mock(ArtifactDao::class);

        $this->artifact_factory                       = Mockery::mock(Tracker_ArtifactFactory::class);
        $this->definition_retriever                   = Mockery::mock(DefinitionForExecutionRetriever::class);
        $this->execution_with_automated_data_provider = Mockery::mock(ExecutionWithAutomatedTestDataProvider::class);

        $this->list_of_executions_with_automated_test_data_retriever = new ListOfExecutionsWithAutomatedTestDataRetriever(
            $this->config,
            $this->artifact_dao,
            $this->definition_retriever,
            $this->execution_with_automated_data_provider,
            $this->artifact_factory
        );
    }

    public function testGetExecutionsWithAutomatedTestData(): void
    {
        $definition_1 = Mockery::mock(Artifact::class);
        $execution_1  = Mockery::mock(Artifact::class);

        $this->artifact_dao->shouldReceive('searchExecutionArtifactsForCampaign')->andReturn(
            [["execution_1"]]
        );

        $this->config->shouldReceive('getTestExecutionTrackerId')->andReturn(42);

        $execution_with_automated_test = Mockery::mock(ExecutionWithAutomatedTestData::class);

        $this->artifact_factory
            ->shouldReceive('getInstanceFromRow')
            ->with(["execution_1"])
            ->andReturn($execution_1);

        $this->execution_with_automated_data_provider
            ->shouldReceive('getExecutionWithAutomatedTestData')
            ->withArgs([$execution_1, $definition_1, $this->user])
            ->andReturn($execution_with_automated_test);

        $this->definition_retriever->shouldReceive('getDefinitionRepresentationForExecution')->andReturn($definition_1);

        $result = $this->list_of_executions_with_automated_test_data_retriever->getExecutionsWithAutomatedTestData(
            $this->artifact,
            $this->user
        );

        $this->assertEquals([$execution_with_automated_test], $result);
    }

    public function testGetExecutionsWithAutomatedTestDataReturnEmptyIfNoDefinition(): void
    {
        $execution_1  = Mockery::mock(Artifact::class);

        $this->artifact_dao->shouldReceive('searchExecutionArtifactsForCampaign')->andReturn(
            [["execution_1"]]
        );

        $this->config->shouldReceive('getTestExecutionTrackerId')->andReturn(42);

        $this->artifact_factory
            ->shouldReceive('getInstanceFromRow')
            ->with(["execution_1"])
            ->andReturn($execution_1);

        $this->execution_with_automated_data_provider
            ->shouldReceive('getExecutionWithAutomatedTestData')->never();

        $this->definition_retriever->shouldReceive('getDefinitionRepresentationForExecution')->andThrow(
            Mockery::mock(DefinitionNotFoundException::class)
        );

        $result = $this->list_of_executions_with_automated_test_data_retriever->getExecutionsWithAutomatedTestData(
            $this->artifact,
            $this->user
        );

        $this->assertEquals([], $result);
    }

    public function testGetExecutionsWithAutomatedTestDataReturnEmptyIfNoExecution(): void
    {
        $this->artifact_dao->shouldReceive('searchExecutionArtifactsForCampaign')->never();

        $this->config->shouldReceive('getTestExecutionTrackerId')->andReturn(null);

        $result = $this->list_of_executions_with_automated_test_data_retriever->getExecutionsWithAutomatedTestData(
            $this->artifact,
            $this->user
        );

        $this->assertEquals([], $result);
    }
}
