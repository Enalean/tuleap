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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Reference\Presenters;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalLanguageMock;
use Tuleap\Reference\CrossReference;
use Tuleap\Reference\CrossReferenceCollection;
use Tuleap\Test\PHPUnit\TestCase;

final class CrossReferenceByNaturePresenterBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    private CrossReferenceByNaturePresenterBuilder $builder;
    private CrossReferenceLinkListPresenterBuilder&MockObject $cross_ref_link_list_presenter_builder;
    private CrossReference&MockObject $cross_ref_target_1;
    private CrossReference&MockObject $cross_ref_target_2;
    private CrossReference&MockObject $cross_ref_target_3;
    private CrossReferenceLinkListPresenter&MockObject $cross_reference_link_list;
    private CrossReferenceLinkListPresenter&MockObject $cross_reference_link_list_2;
    private CrossReferenceLinkListPresenter&MockObject $cross_reference_link_list_3;
    private CrossReferenceLinkPresenter&MockObject $cross_ref_link_1;
    private CrossReferenceLinkPresenter&MockObject $cross_ref_link_2;
    private CrossReferenceLinkPresenter&MockObject $cross_ref_link_3;
    private CrossReferenceCollection&MockObject $cross_ref_collection;
    private CrossReferenceLinkPresenterCollectionBuilder&MockObject $cross_ref_link_collection_presenter_builder;


    protected function setUp(): void
    {
        $this->cross_ref_link_list_presenter_builder       = $this->createMock(CrossReferenceLinkListPresenterBuilder::class);
        $this->cross_ref_link_collection_presenter_builder = $this->createMock(CrossReferenceLinkPresenterCollectionBuilder::class);

        $this->cross_reference_link_list   = $this->createMock(CrossReferenceLinkListPresenter::class);
        $this->cross_reference_link_list_2 = $this->createMock(CrossReferenceLinkListPresenter::class);
        $this->cross_reference_link_list_3 = $this->createMock(CrossReferenceLinkListPresenter::class);

        $this->cross_ref_collection = $this->createMock(CrossReferenceCollection::class);

        $this->cross_ref_target_1 = $this->createMock(CrossReference::class);
        $this->cross_ref_target_2 = $this->createMock(CrossReference::class);
        $this->cross_ref_target_3 = $this->createMock(CrossReference::class);

        $this->cross_ref_link_1 = $this->createMock(CrossReferenceLinkPresenter::class);
        $this->cross_ref_link_2 = $this->createMock(CrossReferenceLinkPresenter::class);
        $this->cross_ref_link_3 = $this->createMock(CrossReferenceLinkPresenter::class);

        $this->builder = new CrossReferenceByNaturePresenterBuilder($this->cross_ref_link_list_presenter_builder, $this->cross_ref_link_collection_presenter_builder);

        $GLOBALS['HTML'] = $this->createMock(\Layout::class);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['HTML']);
    }

    public function testItReturnsNullIfThereAreNoCrossRefs(): void
    {
        $this->cross_ref_collection->expects(self::once())->method('getCrossReferencesBoth')->willReturn([]);
        $this->cross_ref_collection->expects(self::once())->method('getCrossReferencesTarget')->willReturn([]);
        $this->cross_ref_collection->expects(self::once())->method('getCrossReferencesSource')->willReturn([]);

        $presenter = $this->builder->build($this->cross_ref_collection, true);
        self::assertEquals(null, $presenter);
    }

    public function testItReturnsTargetCrossReferenceLinkListWithLinkArray(): void
    {
        $this->cross_ref_link_collection_presenter_builder
            ->method('build')
            ->with([$this->cross_ref_target_1], 'target', true)
            ->willReturn([$this->cross_ref_link_1]);

        $this->cross_ref_link_list_presenter_builder
            ->expects(self::once())
            ->method('buildForTarget')
            ->with([$this->cross_ref_link_1])
            ->willReturn($this->cross_reference_link_list);

        $this->cross_ref_collection->expects(self::exactly(2))->method('getCrossReferencesBoth')->willReturn([]);
        $this->cross_ref_collection->expects(self::exactly(3))->method('getCrossReferencesTarget')->willReturn([$this->cross_ref_target_1]);
        $this->cross_ref_collection->expects(self::once())->method('getCrossReferencesSource')->willReturn([]);
        $this->cross_ref_collection->expects(self::once())->method('getLabel')->willReturn('Tracker');

        $presenter = $this->builder->build($this->cross_ref_collection, true);

        self::assertEquals("Tracker", $presenter->nature_label);
        self::assertEquals([$this->cross_reference_link_list], $presenter->cross_reference_link_list);
    }

    public function testItCallsSourceCrossReferenceLinkListWithLinkArray(): void
    {
        $this->cross_ref_link_collection_presenter_builder
            ->method('build')
            ->with([$this->cross_ref_target_1], 'source', true)
            ->willReturn([$this->cross_ref_link_1]);

        $this->cross_ref_link_list_presenter_builder
            ->expects(self::once())
            ->method('buildForSource')
            ->with([$this->cross_ref_link_1])
            ->willReturn($this->cross_reference_link_list);

        $this->cross_ref_collection->expects(self::exactly(2))->method('getCrossReferencesBoth')->willReturn([]);
        $this->cross_ref_collection->expects(self::exactly(2))->method('getCrossReferencesTarget')->willReturn([]);
        $this->cross_ref_collection->expects(self::exactly(3))->method('getCrossReferencesSource')->willReturn([$this->cross_ref_target_1]);
        $this->cross_ref_collection->expects(self::once())->method('getLabel')->willReturn('Tracker');

        $presenter = $this->builder->build($this->cross_ref_collection, true);

        self::assertEquals("Tracker", $presenter->nature_label);
        self::assertEquals([$this->cross_reference_link_list], $presenter->cross_reference_link_list);
    }

    public function testItCallsBothCrossReferenceLinkListWithLinkArrayAndWithinParams(): void
    {
        $this->cross_ref_link_collection_presenter_builder
            ->method('build')
            ->with([$this->cross_ref_target_1], 'both', false)
            ->willReturn([$this->cross_ref_link_1]);

        $this->cross_ref_link_list_presenter_builder
            ->expects(self::once())
            ->method('buildForBoth')
            ->with([$this->cross_ref_link_1])
            ->willReturn($this->cross_reference_link_list);

        $this->cross_ref_collection->expects(self::exactly(3))->method('getCrossReferencesBoth')->willReturn([$this->cross_ref_target_1]);
        $this->cross_ref_collection->expects(self::once())->method('getCrossReferencesTarget')->willReturn([]);
        $this->cross_ref_collection->expects(self::once())->method('getCrossReferencesSource')->willReturn([]);
        $this->cross_ref_collection->expects(self::once())->method('getLabel')->willReturn('Tracker');

        $presenter = $this->builder->build($this->cross_ref_collection, false);

        self::assertEquals("Tracker", $presenter->nature_label);
        self::assertEquals([$this->cross_reference_link_list], $presenter->cross_reference_link_list);
    }

    public function testItCallsSourceAndBothAndTargetCrossReferenceLinkListWithLinkArray(): void
    {
        $this->cross_ref_link_collection_presenter_builder
            ->method('build')
            ->withConsecutive(
                [[$this->cross_ref_target_3], 'both', true],
                [[$this->cross_ref_target_2], 'target', true],
                [[$this->cross_ref_target_1], 'source', true],
            )
            ->willReturnOnConsecutiveCalls(
                [$this->cross_ref_link_3],
                [$this->cross_ref_link_2],
                [$this->cross_ref_link_1],
            );

        $this->cross_ref_link_list_presenter_builder
            ->method('buildForSource')
            ->with([$this->cross_ref_link_1])
            ->willReturn($this->cross_reference_link_list);

        $this->cross_ref_link_list_presenter_builder
            ->method('buildForTarget')
            ->with([$this->cross_ref_link_2])
            ->willReturn($this->cross_reference_link_list_2);

        $this->cross_ref_link_list_presenter_builder
            ->method('buildForBoth')
            ->with([$this->cross_ref_link_3])
            ->willReturn($this->cross_reference_link_list_3);

        $this->cross_ref_collection->expects(self::exactly(3))->method('getCrossReferencesBoth')->willReturn([$this->cross_ref_target_3]);
        $this->cross_ref_collection->expects(self::exactly(2))->method('getCrossReferencesTarget')->willReturn([$this->cross_ref_target_2]);
        $this->cross_ref_collection->expects(self::exactly(2))->method('getCrossReferencesSource')->willReturn([$this->cross_ref_target_1]);
        $this->cross_ref_collection->expects(self::once())->method('getLabel')->willReturn('Tracker');

        $presenter = $this->builder->build($this->cross_ref_collection, true);

        self::assertEquals("Tracker", $presenter->nature_label);
        self::assertEquals([$this->cross_reference_link_list, $this->cross_reference_link_list_2, $this->cross_reference_link_list_3], $presenter->cross_reference_link_list);
    }
}
