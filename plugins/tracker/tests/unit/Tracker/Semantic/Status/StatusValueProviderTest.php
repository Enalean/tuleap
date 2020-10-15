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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tuleap\Tracker\Artifact\Artifact;

class StatusValueProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var StatusValueForChangesetProvider
     */
    private $provider;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|StatusValueProvider
     */
    private $for_changeset_provider;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact
     */
    private $artifact;

    protected function setUp(): void
    {
        $this->artifact = Mockery::mock(Artifact::class);

        $this->user = Mockery::mock(PFUser::class);

        $this->for_changeset_provider = Mockery::mock(StatusValueForChangesetProvider::class);
        $this->provider = new StatusValueProvider($this->for_changeset_provider);
    }

    public function testItReturnsNullIfNoLastChangeset(): void
    {
        $this->artifact->shouldReceive('getLastChangeset')->andReturnNull();

        $this->assertNull($this->provider->getStatusValue($this->artifact, $this->user));
    }

    public function testItReturnsTheStatusValueForTheLastChangeset(): void
    {
        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->artifact->shouldReceive('getLastChangeset')->andReturn($changeset);

        $value = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $this->for_changeset_provider
            ->shouldReceive('getStatusValueForChangeset')
            ->with($changeset, $this->user)
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->provider->getStatusValue($this->artifact, $this->user)
        );
    }
}
