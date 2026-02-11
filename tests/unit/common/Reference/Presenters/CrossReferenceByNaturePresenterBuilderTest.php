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

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\GlobalLanguageMock;
use Tuleap\Reference\CrossReference;
use Tuleap\Reference\CrossReferenceCollection;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CrossReferenceByNaturePresenterBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    private CrossReferenceByNaturePresenterBuilder $builder;
    private CrossReferenceLinkListPresenterBuilder&Stub $cross_ref_link_list_presenter_builder;
    private CrossReference&Stub $cross_ref_target_1;
    private CrossReference&Stub $cross_ref_target_2;
    private CrossReference&Stub $cross_ref_target_3;
    private CrossReferenceLinkListPresenter&Stub $cross_reference_link_list;
    private CrossReferenceLinkListPresenter&Stub $cross_reference_link_list_2;
    private CrossReferenceLinkListPresenter&Stub $cross_reference_link_list_3;
    private CrossReferenceLinkPresenter&Stub $cross_ref_link_1;
    private CrossReferenceLinkPresenter&Stub $cross_ref_link_2;
    private CrossReferenceLinkPresenter&Stub $cross_ref_link_3;
    private CrossReferenceCollection&Stub $cross_ref_collection;
    private CrossReferenceLinkPresenterCollectionBuilder&Stub $cross_ref_link_collection_presenter_builder;


    #[\Override]
    protected function setUp(): void
    {
        $this->cross_ref_link_list_presenter_builder       = $this->createStub(CrossReferenceLinkListPresenterBuilder::class);
        $this->cross_ref_link_collection_presenter_builder = $this->createStub(CrossReferenceLinkPresenterCollectionBuilder::class);

        $this->cross_reference_link_list   = $this->createStub(CrossReferenceLinkListPresenter::class);
        $this->cross_reference_link_list_2 = $this->createStub(CrossReferenceLinkListPresenter::class);
        $this->cross_reference_link_list_3 = $this->createStub(CrossReferenceLinkListPresenter::class);

        $this->cross_ref_collection = $this->createStub(CrossReferenceCollection::class);

        $this->cross_ref_target_1 = $this->createStub(CrossReference::class);
        $this->cross_ref_target_2 = $this->createStub(CrossReference::class);
        $this->cross_ref_target_3 = $this->createStub(CrossReference::class);

        $this->cross_ref_link_1 = $this->createStub(CrossReferenceLinkPresenter::class);
        $this->cross_ref_link_2 = $this->createStub(CrossReferenceLinkPresenter::class);
        $this->cross_ref_link_3 = $this->createStub(CrossReferenceLinkPresenter::class);

        $this->builder = new CrossReferenceByNaturePresenterBuilder($this->cross_ref_link_list_presenter_builder, $this->cross_ref_link_collection_presenter_builder);

        $GLOBALS['HTML'] = $this->createStub(\Layout::class);
    }

    #[\Override]
    protected function tearDown(): void
    {
        unset($GLOBALS['HTML']);
    }

    public function testItReturnsNullIfThereAreNoCrossRefs(): void
    {
        $this->cross_ref_collection->method('getCrossReferencesBoth')->willReturn([]);
        $this->cross_ref_collection->method('getCrossReferencesTarget')->willReturn([]);
        $this->cross_ref_collection->method('getCrossReferencesSource')->willReturn([]);

        $presenter = $this->builder->build($this->cross_ref_collection, true);
        self::assertEquals(null, $presenter);
    }

    public function testItReturnsTargetCrossReferenceLinkListWithLinkArray(): void
    {
        $this->cross_ref_link_collection_presenter_builder
            ->method('build')
            ->willReturnMap([[[$this->cross_ref_target_1], 'target', true, [$this->cross_ref_link_1]]]);

        $this->cross_ref_link_list_presenter_builder
            ->method('buildForTarget')
            ->willReturnMap([[[$this->cross_ref_link_1], $this->cross_reference_link_list]]);

        $this->cross_ref_collection->method('getCrossReferencesBoth')->willReturn([]);
        $this->cross_ref_collection->method('getCrossReferencesTarget')->willReturn([$this->cross_ref_target_1]);
        $this->cross_ref_collection->method('getCrossReferencesSource')->willReturn([]);
        $this->cross_ref_collection->method('getLabel')->willReturn('Tracker');

        $presenter = $this->builder->build($this->cross_ref_collection, true);

        self::assertEquals('Tracker', $presenter->nature_label);
        self::assertEquals([$this->cross_reference_link_list], $presenter->cross_reference_link_list);
    }

    public function testItCallsSourceCrossReferenceLinkListWithLinkArray(): void
    {
        $this->cross_ref_link_collection_presenter_builder
            ->method('build')
            ->willReturnMap([[[$this->cross_ref_target_1], 'source', true, [$this->cross_ref_link_1]]]);

        $this->cross_ref_link_list_presenter_builder
            ->method('buildForSource')
            ->willReturnMap([[[$this->cross_ref_link_1], $this->cross_reference_link_list]]);

        $this->cross_ref_collection->method('getCrossReferencesBoth')->willReturn([]);
        $this->cross_ref_collection->method('getCrossReferencesTarget')->willReturn([]);
        $this->cross_ref_collection->method('getCrossReferencesSource')->willReturn([$this->cross_ref_target_1]);
        $this->cross_ref_collection->method('getLabel')->willReturn('Tracker');

        $presenter = $this->builder->build($this->cross_ref_collection, true);

        self::assertEquals('Tracker', $presenter->nature_label);
        self::assertEquals([$this->cross_reference_link_list], $presenter->cross_reference_link_list);
    }

    public function testItCallsBothCrossReferenceLinkListWithLinkArrayAndWithinParams(): void
    {
        $this->cross_ref_link_collection_presenter_builder
            ->method('build')
            ->willReturnMap([[[$this->cross_ref_target_1], 'both', false, [$this->cross_ref_link_1]]]);

        $this->cross_ref_link_list_presenter_builder
            ->method('buildForBoth')
            ->willReturnMap([[[$this->cross_ref_link_1], $this->cross_reference_link_list]]);

        $this->cross_ref_collection->method('getCrossReferencesBoth')->willReturn([$this->cross_ref_target_1]);
        $this->cross_ref_collection->method('getCrossReferencesTarget')->willReturn([]);
        $this->cross_ref_collection->method('getCrossReferencesSource')->willReturn([]);
        $this->cross_ref_collection->method('getLabel')->willReturn('Tracker');

        $presenter = $this->builder->build($this->cross_ref_collection, false);

        self::assertEquals('Tracker', $presenter->nature_label);
        self::assertEquals([$this->cross_reference_link_list], $presenter->cross_reference_link_list);
    }

    public function testItCallsSourceAndBothAndTargetCrossReferenceLinkListWithLinkArray(): void
    {
        $this->cross_ref_link_collection_presenter_builder->method('build')
            ->willReturnOnConsecutiveCalls([$this->cross_ref_link_3], [$this->cross_ref_link_2], [$this->cross_ref_link_1]);

        $this->cross_ref_link_list_presenter_builder
            ->method('buildForSource')
            ->willReturnMap([[[$this->cross_ref_link_1], $this->cross_reference_link_list]]);

        $this->cross_ref_link_list_presenter_builder
            ->method('buildForTarget')
            ->willReturnMap([[[$this->cross_ref_link_2], $this->cross_reference_link_list_2]]);

        $this->cross_ref_link_list_presenter_builder
            ->method('buildForBoth')
            ->willReturnMap([[[$this->cross_ref_link_3], $this->cross_reference_link_list_3]]);

        $this->cross_ref_collection->method('getCrossReferencesBoth')->willReturn([$this->cross_ref_target_3]);
        $this->cross_ref_collection->method('getCrossReferencesTarget')->willReturn([$this->cross_ref_target_2]);
        $this->cross_ref_collection->method('getCrossReferencesSource')->willReturn([$this->cross_ref_target_1]);
        $this->cross_ref_collection->method('getLabel')->willReturn('Tracker');

        $presenter = $this->builder->build($this->cross_ref_collection, true);

        self::assertEquals('Tracker', $presenter->nature_label);
        self::assertEquals([$this->cross_reference_link_list, $this->cross_reference_link_list_2, $this->cross_reference_link_list_3], $presenter->cross_reference_link_list);
    }
}
