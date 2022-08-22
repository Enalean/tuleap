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
use Reference;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class ReferencesBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ReferencesBuilder $builder;
    /**
     * @var Dao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;
    /**
     * @var GitRepositoryFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao                = $this->createMock(Dao::class);
        $this->repository_factory = $this->createMock(GitRepositoryFactory::class);

        $this->builder = new ReferencesBuilder(
            $this->dao,
            $this->repository_factory
        );
    }

    public function testItRetrievesAReference(): void
    {
        $this->dao->expects(self::once())
            ->method('getRef')
            ->with('cmmt123')
            ->willReturn([
                'repository_id' => 1,
                'sha1' => 'commitsha1',
            ]);

        $repository = new GitRepository();
        $repository->setProject(ProjectTestBuilder::aProject()->build());
        $repository->setId(1);

        $this->repository_factory->expects(self::once())
            ->method('getRepositoryById')
            ->with(1)
            ->willReturn($repository);

        $reference = $this->builder->getReference(
            'cmmt',
            123
        );

        self::assertInstanceOf(Reference::class, $reference);

        self::assertSame('plugin_git', $reference->getServiceShortName());
        self::assertSame("/plugins/git/index.php/101/view/1/?a=commit&h=commitsha1", $reference->getLink());
    }

    public function testItReturnsNullIfKeywordIsNotKnown(): void
    {
        self::assertNull(
            $this->builder->getReference(
                'whatever',
                123
            )
        );
    }

    public function testItReturnsNullIfNoEntryFoundInDB(): void
    {
        $this->dao->expects(self::once())
            ->method('getRef')
            ->with('cmmt123')
            ->willReturn(null);

        self::assertNull(
            $this->builder->getReference(
                'cmmt',
                123
            )
        );
    }

    public function testItReturnsNullIfTargetRepositoryNotFound(): void
    {
        $this->dao->expects(self::once())
            ->method('getRef')
            ->with('cmmt123')
            ->willReturn([
                'repository_id' => 1,
                'sha1' => 'commitsha1',
            ]);

        $this->repository_factory->expects(self::once())
            ->method('getRepositoryById')
            ->with(1)
            ->willReturn(null);

        self::assertNull(
            $this->builder->getReference(
                'cmmt',
                123
            )
        );
    }
}
