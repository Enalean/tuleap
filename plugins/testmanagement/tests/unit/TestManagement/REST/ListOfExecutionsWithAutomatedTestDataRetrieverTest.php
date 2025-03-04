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

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_ArtifactFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\Campaign\Execution\DefinitionForExecutionRetriever;
use Tuleap\TestManagement\Campaign\Execution\DefinitionNotFoundException;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\REST\v1\ExecutionWithAutomatedTestData;
use Tuleap\TestManagement\REST\v1\ExecutionWithAutomatedTestDataProvider;
use Tuleap\TestManagement\REST\v1\ListOfExecutionsWithAutomatedTestDataRetriever;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ListOfExecutionsWithAutomatedTestDataRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Config&MockObject $config;
    private ArtifactDao&MockObject $artifact_dao;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private DefinitionForExecutionRetriever&MockObject $definition_retriever;
    private ExecutionWithAutomatedTestDataProvider&MockObject $execution_with_automated_data_provider;
    private Artifact $artifact;
    private ListOfExecutionsWithAutomatedTestDataRetriever $list_of_executions_with_automated_test_data_retriever;
    private PFUser $user;

    protected function setUp(): void
    {
        $this->user = UserTestBuilder::buildWithDefaults();

        $this->artifact = ArtifactTestBuilder::anArtifact(102)->build();

        $this->config = $this->createMock(Config::class);

        $this->artifact_dao = $this->createMock(ArtifactDao::class);

        $this->artifact_factory                       = $this->createMock(Tracker_ArtifactFactory::class);
        $this->definition_retriever                   = $this->createMock(DefinitionForExecutionRetriever::class);
        $this->execution_with_automated_data_provider = $this->createMock(ExecutionWithAutomatedTestDataProvider::class);

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
        $definition_1 = $this->createMock(Artifact::class);
        $execution_1  = $this->createMock(Artifact::class);

        $this->artifact_dao->method('searchExecutionArtifactsForCampaign')->willReturn(
            [['execution_1']]
        );

        $this->config->method('getTestExecutionTrackerId')->willReturn(42);

        $execution_with_automated_test = $this->createMock(ExecutionWithAutomatedTestData::class);

        $this->artifact_factory
            ->method('getInstanceFromRow')
            ->with(['execution_1'])
            ->willReturn($execution_1);

        $this->execution_with_automated_data_provider
            ->method('getExecutionWithAutomatedTestData')
            ->with($execution_1, $definition_1, $this->user)
            ->willReturn($execution_with_automated_test);

        $this->definition_retriever->method('getDefinitionRepresentationForExecution')->willReturn($definition_1);

        $result = $this->list_of_executions_with_automated_test_data_retriever->getExecutionsWithAutomatedTestData(
            $this->artifact,
            $this->user
        );

        $this->assertEquals([$execution_with_automated_test], $result);
    }

    public function testGetExecutionsWithAutomatedTestDataReturnEmptyIfNoDefinition(): void
    {
        $execution_1 = $this->createMock(Artifact::class);

        $this->artifact_dao->method('searchExecutionArtifactsForCampaign')->willReturn(
            [['execution_1']]
        );

        $this->config->method('getTestExecutionTrackerId')->willReturn(42);

        $this->artifact_factory
            ->method('getInstanceFromRow')
            ->with(['execution_1'])
            ->willReturn($execution_1);

        $this->execution_with_automated_data_provider
            ->expects(self::never())
            ->method('getExecutionWithAutomatedTestData');

        $this->definition_retriever->method('getDefinitionRepresentationForExecution')->willThrowException(
            $this->createMock(DefinitionNotFoundException::class)
        );

        $result = $this->list_of_executions_with_automated_test_data_retriever->getExecutionsWithAutomatedTestData(
            $this->artifact,
            $this->user
        );

        $this->assertEquals([], $result);
    }

    public function testGetExecutionsWithAutomatedTestDataReturnEmptyIfNoExecution(): void
    {
        $this->artifact_dao->expects(self::never())->method('searchExecutionArtifactsForCampaign');

        $this->config->method('getTestExecutionTrackerId')->willReturn(null);

        $result = $this->list_of_executions_with_automated_test_data_retriever->getExecutionsWithAutomatedTestData(
            $this->artifact,
            $this->user
        );

        $this->assertEquals([], $result);
    }
}
