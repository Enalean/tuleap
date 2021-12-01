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

namespace Tuleap\ReferenceAliasCore;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use ProjectManager;
use Reference;

class ReferencesBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ReferencesBuilder
     */
    private $builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Dao
     */
    private $dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao             = Mockery::mock(Dao::class);
        $this->project_manager = Mockery::mock(ProjectManager::class);

        $this->builder = new ReferencesBuilder(
            $this->dao,
            $this->project_manager
        );
    }

    public function testItRetrievesAPkgReference(): void
    {
        $project = Project::buildForTest();

        $this->dao->shouldReceive('getRef')
            ->once()
            ->with('pkg123')
            ->andReturn([
                'project_id' => 101,
                'target' => '01',
            ]);

        $reference = $this->builder->getReference(
            $project,
            'pkg',
            123
        );

        $this->assertInstanceOf(Reference::class, $reference);

        $this->assertSame('frs', $reference->getServiceShortName());
        $this->assertSame("/file/showfiles.php?group_id=101#p_01", $reference->getLink());
    }

    public function testItRetrievesARelReference(): void
    {
        $project = Project::buildForTest();

        $this->dao->shouldReceive('getRef')
            ->once()
            ->with('rel123')
            ->andReturn([
                'project_id' => 101,
                'target' => '01',
            ]);

        $reference = $this->builder->getReference(
            $project,
            'rel',
            123
        );

        $this->assertInstanceOf(Reference::class, $reference);

        $this->assertSame('frs', $reference->getServiceShortName());
        $this->assertSame("/file/shownotes.php?release_id=01", $reference->getLink());
    }

    public function testItReturnsNullIfNoEntryFoundInDB(): void
    {
        $project = Project::buildForTest();

        $this->dao->shouldReceive('getRef')
            ->once()
            ->andReturn([]);

        $this->assertNull(
            $this->builder->getReference(
                $project,
                'rel',
                123
            )
        );
    }

    public function testItReturnsNullIfKeywordIsNotKnown(): void
    {
        $project = Project::buildForTest();

        $this->dao->shouldReceive('getRef')
            ->once()
            ->with('whatever123')
            ->andReturn([
                'project_id' => 101,
                'target' => '01',
            ]);

        $this->assertNull(
            $this->builder->getReference(
                $project,
                'whatever',
                123
            )
        );
    }
}
