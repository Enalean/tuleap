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

namespace Tuleap\ReferenceAliasGit;

use GitRepository;
use GitRepositoryFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
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
     * @var GitRepositoryFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $repository_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao                = Mockery::mock(Dao::class);
        $this->repository_factory = Mockery::mock(GitRepositoryFactory::class);

        $this->builder = new ReferencesBuilder(
            $this->dao,
            $this->repository_factory
        );
    }

    public function testItRetrievesAReference(): void
    {
        $this->dao->shouldReceive('getRef')
            ->once()
            ->with('cmmt123')
            ->andReturn([
                'repository_id' => 1,
                'sha1' => 'commitsha1',
            ]);

        $repository = Mockery::mock(GitRepository::class);
        $repository->shouldReceive('getProjectId')->andReturn(101);
        $repository->shouldReceive('getId')->andReturn(1);

        $this->repository_factory->shouldReceive('getRepositoryById')
            ->once()
            ->with(1)
            ->andReturn($repository);

        $reference = $this->builder->getReference(
            'cmmt',
            123
        );

        $this->assertInstanceOf(Reference::class, $reference);

        $this->assertSame('plugin_git', $reference->getServiceShortName());
        $this->assertSame("/plugins/git/index.php/101/view/1/?a=commit&h=commitsha1", $reference->getLink());
    }

    public function testItReturnsNullIfKeywordIsNotKnown(): void
    {
        $this->assertNull(
            $this->builder->getReference(
                'whatever',
                123
            )
        );
    }

    public function testItReturnsNullIfNoEntryFoundInDB(): void
    {
        $this->dao->shouldReceive('getRef')
            ->once()
            ->with('cmmt123')
            ->andReturnNull();

        $this->assertNull(
            $this->builder->getReference(
                'cmmt',
                123
            )
        );
    }

    public function testItReturnsNullIfTargetRepositoryNotFound(): void
    {
        $this->dao->shouldReceive('getRef')
            ->once()
            ->with('cmmt123')
            ->andReturn([
                'repository_id' => 1,
                'sha1' => 'commitsha1',
            ]);

        $this->repository_factory->shouldReceive('getRepositoryById')
            ->once()
            ->with(1)
            ->andReturnNull();

        $this->assertNull(
            $this->builder->getReference(
                'cmmt',
                123
            )
        );
    }
}
