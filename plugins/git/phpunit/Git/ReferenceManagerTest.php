<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Git_ReferenceManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $project;
    private $repository_factory;
    private $reference_manager;
    private $repository;
    private $git_reference_manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project               = \Mockery::spy(\Project::class);
        $this->project->shouldReceive('getId')->andReturns(101);
        $this->project->shouldReceive('getUnixName')->andReturns('gpig');
        $this->repository_factory    = \Mockery::spy(\GitRepositoryFactory::class);
        $this->reference_manager     = \Mockery::spy(\ReferenceManager::class);
        $this->repository            = \Mockery::spy(\GitRepository::class);
        $this->repository->shouldReceive('getId')->andReturns(222);
        $this->git_reference_manager = new Git_ReferenceManager($this->repository_factory, $this->reference_manager);
    }

    public function testItLoadsTheRepositoryWhenRootRepo(): void
    {
        $this->repository_factory->shouldReceive('getRepositoryByPath')->with(101, 'gpig/rantanplan.git')->once();
        $this->git_reference_manager->getReference($this->project, Git::REFERENCE_KEYWORD, 'rantanplan/469eaa9');
    }

    public function testItLoadsTheRepositoryWhenRepoInHierarchy(): void
    {
        $this->repository_factory->shouldReceive('getRepositoryByPath')->with(101, 'gpig/dev/x86_64/rantanplan.git')->once();
        $this->git_reference_manager->getReference($this->project, Git::REFERENCE_KEYWORD, 'dev/x86_64/rantanplan/469eaa9');
    }

    public function testItLoadTheReferenceFromDb(): void
    {
        $this->repository_factory->shouldReceive('getRepositoryByPath')->andReturns($this->repository);

        $this->reference_manager->shouldReceive('loadReferenceFromKeywordAndNumArgs')->with(Git::REFERENCE_KEYWORD, 101, 2, 'rantanplan/469eaa9')->once();

        $this->git_reference_manager->getReference($this->project, Git::REFERENCE_KEYWORD, 'rantanplan/469eaa9');
    }

    public function testItReturnsTheReference(): void
    {
        $reference = \Mockery::spy(\Reference::class);
        $this->repository_factory->shouldReceive('getRepositoryByPath')->andReturns($this->repository);
        $this->reference_manager->shouldReceive('loadReferenceFromKeywordAndNumArgs')->andReturns($reference);

        $ref = $this->git_reference_manager->getReference($this->project, Git::REFERENCE_KEYWORD, 'rantanplan/469eaa9');
        $this->assertEquals($reference, $ref);
    }
}
