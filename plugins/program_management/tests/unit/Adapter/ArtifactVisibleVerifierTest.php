<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter;

use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class ArtifactVisibleVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private UserIdentifier $user_identifier;
    private RetrieveUserStub $user_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_ArtifactFactory
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        $pfuser                = UserTestBuilder::aUser()->build();
        $this->user_identifier = UserIdentifierStub::buildGenericUser();
        $this->user_retriever  = RetrieveUserStub::withUser($pfuser);
        $this->tracker_factory = $this->createMock(\Tracker_ArtifactFactory::class);
    }

    private function getAdapter(): ArtifactVisibleVerifier
    {
        return new ArtifactVisibleVerifier($this->tracker_factory, $this->user_retriever);
    }

    public function testItReturnsTrue(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(172)->build();
        $this->tracker_factory->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->willReturn($artifact);
        self::assertTrue($this->getAdapter()->isVisible(172, $this->user_identifier));
    }

    public function testItReturnsFalse(): void
    {
        $this->tracker_factory->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->willReturn(null);
        self::assertFalse($this->getAdapter()->isVisible(404, $this->user_identifier));
    }
}
