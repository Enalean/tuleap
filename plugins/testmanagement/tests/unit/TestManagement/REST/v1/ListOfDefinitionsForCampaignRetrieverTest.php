<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tracker_ArtifactFactory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\Campaign\Execution\DefinitionForExecutionRetriever;
use Tuleap\TestManagement\Campaign\Execution\DefinitionNotFoundException;
use Tuleap\TestManagement\REST\v1\Execution\ListOfDefinitionsForCampaignRetriever;

class ListOfDefinitionsForCampaignRetrieverTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $user;
    /**
     * @var ArtifactDao|Mockery\LegacyMockInterface|Mockery\MockInterface
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
     * @var ListOfDefinitionsForCampaignRetriever
     */
    private $list_of_definition_retriever;
    /**
     * @var Artifact|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $execution1;
    /**
     * @var Artifact|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $execution2;
    /**
     * @var Artifact|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $execution3;
    /**
     * @var Artifact|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $def1;
    /**
     * @var Artifact|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $def2;
    /**
     * @var Artifact|Mockery\MockInterface
     */
    private $campaign_artifact;

    protected function setUp(): void
    {
        $this->user = Mockery::mock(\PFUser::class);

        $this->campaign_artifact = Mockery::mock(Artifact::class);
        $this->campaign_artifact->shouldReceive("getId")->andReturn(12);

        $this->execution1 = Mockery::mock(Artifact::class);
        $this->execution2 = Mockery::mock(Artifact::class);
        $this->execution3 = Mockery::mock(Artifact::class);

        $this->def1 = Mockery::mock(Artifact::class);
        $this->def1->shouldReceive("getId")->andReturn(42);
        $this->def2 = Mockery::mock(Artifact::class);
        $this->def2->shouldReceive("getId")->andReturn(43);

        $this->artifact_dao         = Mockery::mock(ArtifactDao::class);
        $this->artifact_factory     = Mockery::mock(Tracker_ArtifactFactory::class);
        $this->definition_retriever = Mockery::mock(DefinitionForExecutionRetriever::class);


        $this->list_of_definition_retriever = new ListOfDefinitionsForCampaignRetriever(
            $this->artifact_dao,
            $this->artifact_factory,
            $this->definition_retriever
        );
    }

    public function testGetDefinitionListForCampaign(): void
    {
        $this->artifact_dao->shouldReceive("searchExecutionArtifactsForCampaign")->withArgs([12, 666])->andReturn(
            [1, 2, 3]
        );
        $this->artifact_factory->shouldReceive("getInstanceFromRow")->withArgs([1])->andReturn($this->execution1);
        $this->artifact_factory->shouldReceive("getInstanceFromRow")->withArgs([2])->andReturn($this->execution2);
        $this->artifact_factory->shouldReceive("getInstanceFromRow")->withArgs([3])->andReturn($this->execution3);

        $this->definition_retriever->shouldReceive("getDefinitionRepresentationForExecution")
            ->withArgs([$this->user, $this->execution1])->andReturn($this->def1);
        $this->definition_retriever->shouldReceive("getDefinitionRepresentationForExecution")
            ->withArgs([$this->user, $this->execution2])->andReturn($this->def2);
        $this->definition_retriever->shouldReceive("getDefinitionRepresentationForExecution")
            ->withArgs([$this->user, $this->execution3])->andThrow(Mockery::mock(DefinitionNotFoundException::class));

        $result = $this->list_of_definition_retriever->getDefinitionListForCampaign(
            $this->user,
            $this->campaign_artifact,
            666
        );

        $this->assertEquals(["42" => $this->def1, "43" => $this->def2], $result);
    }
}
