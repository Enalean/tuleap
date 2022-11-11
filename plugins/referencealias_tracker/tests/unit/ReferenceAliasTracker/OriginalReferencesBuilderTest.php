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

use Reference;

final class OriginalReferencesBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private OriginalReferencesBuilder $builder;

    /**
     * @var Dao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao = $this->createMock(Dao::class);

        $this->builder = new OriginalReferencesBuilder(
            $this->dao
        );
    }

    public function testItRetrievesATrackerReference(): void
    {
        $this->dao->expects(self::once())
            ->method('getRef')
            ->with('tracker123')
            ->willReturn([
                'project_id' => 101,
                'target' => 'T2',
            ]);

        $reference = $this->builder->getReference(
            'tracker',
            123
        );

        self::assertInstanceOf(Reference::class, $reference);

        self::assertSame('plugin_tracker', $reference->getServiceShortName());
        self::assertSame("/plugins/tracker/?tracker=T2", $reference->getLink());
    }

    public function testItRetrievesAnArtfReference(): void
    {
        $this->dao->expects(self::once())
            ->method('getRef')
            ->with('artf123')
            ->willReturn([
                'project_id' => 101,
                'target' => '12',
            ]);

        $reference = $this->builder->getReference(
            'artf',
            123
        );

        self::assertInstanceOf(Reference::class, $reference);

        self::assertSame('plugin_tracker', $reference->getServiceShortName());
        self::assertSame("/plugins/tracker/?aid=12", $reference->getLink());
    }

    public function testItRetrievesAPlanReference(): void
    {
        $this->dao->expects(self::once())
            ->method('getRef')
            ->with('plan123')
            ->willReturn([
                'project_id' => 101,
                'target' => '12',
            ]);

        $reference = $this->builder->getReference(
            'plan',
            123
        );

        self::assertInstanceOf(Reference::class, $reference);

        self::assertSame('plugin_tracker', $reference->getServiceShortName());
        self::assertSame("/plugins/tracker/?aid=12", $reference->getLink());
    }

    public function testItReturnsNullIfNoEntryFoundInDB(): void
    {
        $this->dao->expects(self::once())
            ->method('getRef')
            ->willReturn([]);

        self::assertNull(
            $this->builder->getReference(
                'tracker',
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
                'target' => '01',
            ]);

        self::assertNull(
            $this->builder->getReference(
                'whatever',
                123
            )
        );
    }
}
