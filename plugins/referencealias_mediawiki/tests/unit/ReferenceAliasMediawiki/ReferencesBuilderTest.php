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

namespace Tuleap\ReferenceAliasMediawiki;

use ProjectManager;
use Reference;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class ReferencesBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ReferencesBuilder $builder;

    /**
     * @var CompatibilityDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;
    /**
     * @var ProjectManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $project_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao             = $this->createMock(CompatibilityDao::class);
        $this->project_manager = $this->createMock(ProjectManager::class);

        $this->builder = new ReferencesBuilder(
            $this->dao,
            $this->project_manager
        );
    }

    public function testItRetrievesAReference(): void
    {
        $this->dao->expects(self::once())
            ->method('getRef')
            ->with('wiki123')
            ->willReturn([
                'project_id' => 101,
                'target' => 'HomePage',
            ]);

        $reference = $this->builder->getReference(
            ProjectTestBuilder::aProject()->withUnixName('project01')->build(),
            'wiki',
            123
        );

        self::assertInstanceOf(Reference::class, $reference);

        self::assertSame('mediawiki', $reference->getServiceShortName());
        self::assertSame("plugins/mediawiki/wiki/project01/index.php/HomePage", $reference->getLink());
    }

    public function testItReturnsNullIfNoEntryFoundInDB(): void
    {
        $this->dao->expects(self::once())
            ->method('getRef')
            ->willReturn([]);

        self::assertNull(
            $this->builder->getReference(
                ProjectTestBuilder::aProject()->build(),
                'wiki',
                123
            )
        );
    }

    public function testItReturnsNullIfKeywordIsNotKnown(): void
    {
        $this->dao->expects(self::once())
            ->method('getRef')
            ->with('whatever123')
            ->willReturn([
                'project_id' => 101,
                'target' => 'HomePage',
            ]);

        self::assertNull(
            $this->builder->getReference(
                ProjectTestBuilder::aProject()->build(),
                'whatever',
                123
            )
        );
    }
}
