<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\UserStory;

use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\UserStoryIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;

final class IsOpenRetrieverTest extends TestCase
{
    private IsOpenRetriever $is_open_retriever;
    private UserStoryIdentifier $user_story_identifier;
    private RetrieveFullArtifactStub $artifact_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&Artifact
     */
    private $artifact;

    protected function setUp(): void
    {
        $this->artifact              = $this->createStub(Artifact::class);
        $this->artifact_factory      = RetrieveFullArtifactStub::withArtifact($this->artifact);
        $this->is_open_retriever     = new IsOpenRetriever($this->artifact_factory);
        $this->user_story_identifier = UserStoryIdentifierBuilder::withId(2);
    }

    public function testItReturnsValue(): void
    {
        $this->artifact->method('isOpen')->willReturn(true);

        self::assertEquals(true, $this->is_open_retriever->isOpen($this->user_story_identifier));
    }
}
