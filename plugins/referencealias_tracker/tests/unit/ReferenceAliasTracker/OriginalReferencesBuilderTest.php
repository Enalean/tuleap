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

namespace Tuleap\ReferenceAliasTracker;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Reference;

class OriginalReferencesBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var OriginalReferencesBuilder
     */
    private $builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Dao
     */
    private $dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao = Mockery::mock(Dao::class);

        $this->builder = new OriginalReferencesBuilder(
            $this->dao
        );
    }

    public function testItRetrievesATrackerReference(): void
    {
        $this->dao->shouldReceive('getRef')
            ->once()
            ->with('tracker123')
            ->andReturn([
                'project_id' => 101,
                'target' => 'T2',
            ]);

        $reference = $this->builder->getReference(
            'tracker',
            123
        );

        $this->assertInstanceOf(Reference::class, $reference);

        $this->assertSame('plugin_tracker', $reference->getServiceShortName());
        $this->assertSame("/plugins/tracker/?tracker=T2", $reference->getLink());
    }

    public function testItRetrievesAnArtfReference(): void
    {
        $this->dao->shouldReceive('getRef')
            ->once()
            ->with('artf123')
            ->andReturn([
                'project_id' => 101,
                'target' => '12',
            ]);

        $reference = $this->builder->getReference(
            'artf',
            123
        );

        $this->assertInstanceOf(Reference::class, $reference);

        $this->assertSame('plugin_tracker', $reference->getServiceShortName());
        $this->assertSame("/plugins/tracker/?aid=12", $reference->getLink());
    }

    public function testItRetrievesAPlanReference(): void
    {
        $this->dao->shouldReceive('getRef')
            ->once()
            ->with('plan123')
            ->andReturn([
                'project_id' => 101,
                'target' => '12',
            ]);

        $reference = $this->builder->getReference(
            'plan',
            123
        );

        $this->assertInstanceOf(Reference::class, $reference);

        $this->assertSame('plugin_tracker', $reference->getServiceShortName());
        $this->assertSame("/plugins/tracker/?aid=12", $reference->getLink());
    }

    public function testItReturnsNullIfNoEntryFoundInDB(): void
    {
        $this->dao->shouldReceive('getRef')
            ->once()
            ->andReturn([]);

        $this->assertNull(
            $this->builder->getReference(
                'tracker',
                123
            )
        );
    }

    public function testItReturnsNullIfKeywordIsNotKnown(): void
    {
        $this->dao->shouldReceive('getRef')
            ->once()
            ->with('whatever123')
            ->andReturn([
                'project_id' => 101,
                'target' => '01',
            ]);

        $this->assertNull(
            $this->builder->getReference(
                'whatever',
                123
            )
        );
    }
}
