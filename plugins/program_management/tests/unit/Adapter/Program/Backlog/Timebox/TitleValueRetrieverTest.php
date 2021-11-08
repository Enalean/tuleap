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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox;

use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\TimeboxIdentifierStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class TitleValueRetrieverTest extends TestCase
{
    private const ARTIFACT_ID = 1;
    private TimeboxIdentifier $artifact_identifier;
    private Artifact $artifact;

    protected function setUp(): void
    {
        $this->artifact_identifier = TimeboxIdentifierStub::withId(self::ARTIFACT_ID);
        $this->artifact            = ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)->build();
    }

    private function getRetriever(): TitleValueRetriever
    {
        return new TitleValueRetriever(RetrieveFullArtifactStub::withArtifact($this->artifact));
    }

    public function testItReturnsValue(): void
    {
        $this->artifact->setTitle('My artifact');

        self::assertSame('My artifact', $this->getRetriever()->getTitle($this->artifact_identifier));
    }
}
