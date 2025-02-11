<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Status;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

final class StatusValueProviderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private StatusValueProvider $provider;
    private StatusValueForChangesetProvider&MockObject $for_changeset_provider;
    private PFUser $user;

    protected function setUp(): void
    {
        $this->user = UserTestBuilder::buildWithDefaults();

        $this->for_changeset_provider = $this->createMock(StatusValueForChangesetProvider::class);
        $this->provider               = new StatusValueProvider($this->for_changeset_provider);
    }

    public function testItReturnsNullIfNoLastChangeset(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(101)->build();
        $artifact->setChangesets([]);

        $this->assertNull($this->provider->getStatusValue($artifact, $this->user));
    }

    public function testItReturnsTheStatusValueForTheLastChangeset(): void
    {
        $changeset = ChangesetTestBuilder::aChangeset(1002)->build();
        $artifact  = ArtifactTestBuilder::anArtifact(101)
            ->withChangesets($changeset)
            ->build();

        $value = $this->createMock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $this->for_changeset_provider
            ->expects(self::once())
            ->method('getStatusValueForChangeset')
            ->with($changeset, $this->user)
            ->willReturn($value);

        self::assertSame(
            $value,
            $this->provider->getStatusValue($artifact, $this->user)
        );
    }
}
