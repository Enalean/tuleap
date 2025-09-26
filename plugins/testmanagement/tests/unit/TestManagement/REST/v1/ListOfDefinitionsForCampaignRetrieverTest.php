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

namespace Tuleap\TestManagement\REST\v1;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_ArtifactFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\Campaign\Execution\DefinitionForExecutionRetriever;
use Tuleap\TestManagement\Campaign\Execution\DefinitionNotFoundException;
use Tuleap\TestManagement\REST\v1\Execution\ListOfDefinitionsForCampaignRetriever;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ListOfDefinitionsForCampaignRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \PFUser $user;
    private ArtifactDao&MockObject $artifact_dao;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private DefinitionForExecutionRetriever&MockObject $definition_retriever;
    private ListOfDefinitionsForCampaignRetriever $list_of_definition_retriever;
    private Artifact $execution1;
    private Artifact $execution2;
    private Artifact $execution3;
    private Artifact $def1;
    private Artifact $def2;
    private Artifact $campaign_artifact;

    #[\Override]
    protected function setUp(): void
    {
        $this->user = UserTestBuilder::buildWithDefaults();

        $this->campaign_artifact = ArtifactTestBuilder::anArtifact(12)->build();

        $this->execution1 = ArtifactTestBuilder::anArtifact(1)->build();
        $this->execution2 = ArtifactTestBuilder::anArtifact(2)->build();
        $this->execution3 = ArtifactTestBuilder::anArtifact(3)->build();

        $this->def1 = ArtifactTestBuilder::anArtifact(42)->build();
        $this->def2 = ArtifactTestBuilder::anArtifact(43)->build();

        $this->artifact_dao         = $this->createMock(ArtifactDao::class);
        $this->artifact_factory     = $this->createMock(Tracker_ArtifactFactory::class);
        $this->definition_retriever = $this->createMock(DefinitionForExecutionRetriever::class);


        $this->list_of_definition_retriever = new ListOfDefinitionsForCampaignRetriever(
            $this->artifact_dao,
            $this->artifact_factory,
            $this->definition_retriever
        );
    }

    public function testGetDefinitionListForCampaign(): void
    {
        $this->artifact_dao->method('searchExecutionArtifactsForCampaign')
            ->with(12, 666)
            ->willReturn([
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
            ]);

        $this->artifact_factory->method('getInstanceFromRow')
            ->willReturnCallback(fn(array $row) => match ($row) {
                ['id' => 1] => $this->execution1,
                ['id' => 2] => $this->execution2,
                ['id' => 3] => $this->execution3,
            });

        $this->definition_retriever->method('getDefinitionRepresentationForExecution')
            ->willReturnCallback(fn (PFUser $user, Artifact $execution) => match ($execution) {
                $this->execution1 => $this->def1,
                $this->execution2 => $this->def2,
                $this->execution3 => throw new DefinitionNotFoundException($execution),
            });

        $result = $this->list_of_definition_retriever->getDefinitionListForCampaign(
            $this->user,
            $this->campaign_artifact,
            666
        );

        $this->assertEquals(['42' => $this->def1, '43' => $this->def2], $result);
    }
}
