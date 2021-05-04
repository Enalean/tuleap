<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap\REST\v1;

use Luracast\Restler\RestException;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\ForceStrictPHPUnitMock;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use UserManager;

class SubtasksRetrieverTest extends TestCase
{
    use ForceStrictPHPUnitMock;

    private const TASK_ID = 42;

    /**
     * @var SubtasksRetriever
     */
    private $retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $user_manager = $this->createMock(UserManager::class);

        $this->artifact_factory       = $this->createMock(\Tracker_ArtifactFactory::class);
        $representation_builder_cache = new class implements ICacheTaskRepresentationBuilderForTracker {
            public function getRepresentationBuilderForTracker(
                Tracker $tracker,
                \PFUser $user
            ): ?IBuildATaskRepresentation {
                return new class implements IBuildATaskRepresentation {
                    public function buildRepresentation(Artifact $artifact, \PFUser $user): TaskRepresentation
                    {
                        return new TaskRepresentation(
                            $artifact->getId(),
                            $artifact->getXRef(),
                            $artifact->getUri(),
                            (string) $artifact->getTitle(),
                            $artifact->getTracker()->getColor()->getName(),
                            null,
                            "",
                            new \DateTimeImmutable('@1234567890'),
                            new \DateTimeImmutable('@1234567891'),
                            [],
                        );
                    }
                };
            }
        };
        $this->retriever              = new SubtasksRetriever(
            $this->artifact_factory,
            $user_manager,
            $representation_builder_cache
        );

        $this->user = UserTestBuilder::aUser()->build();
        $user_manager->method('getCurrentUser')->willReturn($this->user);
    }

    public function test404IfTaskNotFound(): void
    {
        $this->artifact_factory
            ->method('getArtifactByIdUserCanView')
            ->with($this->user, self::TASK_ID)
            ->willReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->retriever->getTasks(self::TASK_ID, 0, 10);
    }

    public function testPaginatedRepresentationsOfSubtasks(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(42)
            ->withArtifactFactory($this->artifact_factory)
            ->build();

        $this->artifact_factory
            ->method('getArtifactByIdUserCanView')
            ->with($this->user, self::TASK_ID)
            ->willReturn($artifact);

        $this->artifact_factory
            ->method('getChildren')
            ->with($artifact)
            ->willReturn([
                $this->aSubtask(1001, $artifact->getTracker()),
                $this->aSubtask(1002, $artifact->getTracker()),
                $this->aSubtask(1003, $artifact->getTracker()),
            ]);

        $tasks = $this->retriever->getTasks(self::TASK_ID, 1, 1);

        $representations = $tasks->getRepresentations();
        self::assertCount(1, $representations);
        self::assertEquals(3, $tasks->getTotalSize());
        self::assertEquals(1002, $representations[0]->id);
    }

    private function aSubtask(int $id, Tracker $tracker): Artifact
    {
        $subtask = $this->createMock(Artifact::class);
        $subtask->method('getId')->willReturn($id);
        $subtask->method('getXRef')->willReturn('art #' . $id);
        $subtask->method('userCanView')->willReturn(true);
        $subtask->method('getTracker')->willReturn($tracker);
        $subtask->method('getUri')->willReturn('/path/to/' . $id);
        $subtask->method('getTitle')->willReturn('Subtask ' . $id);

        return $subtask;
    }
}
