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

use Tuleap\Reference\CrossReference;
use Tuleap\Reference\CrossReferenceCollection;

final class CrossReferenceByNaturePresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var CrossReferenceByNaturePresenterBuilder
     */
    private $builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CrossReferenceLinkListPresenterBuilder
     */
    private $cross_ref_link_list_presenter_builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CrossReferenceLinkListPresenter
     */
    private $cross_reference_link_list;
    /**
     * @var CrossReference|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $cross_ref_target_1;
    /**
     * @var CrossReference|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $cross_ref_target_2;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CrossReferenceLinkListPresenter
     */
    private $cross_reference_link_list_2;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CrossReferenceLinkPresenterCollectionBuilder
     */
    private $cross_ref_link_collection_presenter_builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CrossReferenceLinkPresenter
     */
    private $cross_ref_link_2;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CrossReferenceLinkPresenter
     */
    private $cross_ref_link_1;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CrossReferenceCollection
     */
    private $cross_ref_collection;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CrossReferenceLinkListPresenter
     */
    private $cross_reference_link_list_3;
    /**
     * @var CrossReference|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $cross_ref_target_3;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CrossReferenceLinkPresenter
     */
    private $cross_ref_link_3;


    protected function setUp(): void
    {
        $this->cross_ref_link_list_presenter_builder       = \Mockery::mock(CrossReferenceLinkListPresenterBuilder::class);
        $this->cross_ref_link_collection_presenter_builder = \Mockery::mock(CrossReferenceLinkPresenterCollectionBuilder::class);

        $this->cross_reference_link_list   = \Mockery::mock(CrossReferenceLinkListPresenter::class);
        $this->cross_reference_link_list_2 = \Mockery::mock(CrossReferenceLinkListPresenter::class);
        $this->cross_reference_link_list_3 = \Mockery::mock(CrossReferenceLinkListPresenter::class);

        $this->cross_ref_collection = \Mockery::mock(CrossReferenceCollection::class);

        $this->cross_ref_target_1 = \Mockery::mock(CrossReference::class);
        $this->cross_ref_target_2 = \Mockery::mock(CrossReference::class);
        $this->cross_ref_target_3 = \Mockery::mock(CrossReference::class);

        $this->cross_ref_link_1 = \Mockery::mock(CrossReferenceLinkPresenter::class);
        $this->cross_ref_link_2 = \Mockery::mock(CrossReferenceLinkPresenter::class);
        $this->cross_ref_link_3 = \Mockery::mock(CrossReferenceLinkPresenter::class);

        $this->builder = new CrossReferenceByNaturePresenterBuilder($this->cross_ref_link_list_presenter_builder, $this->cross_ref_link_collection_presenter_builder);

        $GLOBALS['Language'] = \Mockery::mock(\BaseLanguage::class);
        $GLOBALS['HTML']     = \Mockery::spy(\Layout::class);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['Language']);
        unset($GLOBALS['HTML']);
    }

    public function testItReturnsNullIfThereAreNoCrossRefs(): void
    {
        $this->cross_ref_collection->shouldReceive('getCrossReferencesBoth')->once()->andReturn([]);
        $this->cross_ref_collection->shouldReceive('getCrossReferencesTarget')->once()->andReturn([]);
        $this->cross_ref_collection->shouldReceive('getCrossReferencesSource')->once()->andReturn([]);

        $presenter = $this->builder->build($this->cross_ref_collection, true);
        $this->assertEquals(null, $presenter);
    }

    public function testItReturnsTargetCrossReferenceLinkListWithLinkArray(): void
    {
        $this->cross_ref_link_collection_presenter_builder
            ->shouldReceive('build')
            ->with([$this->cross_ref_target_1], 'target', true)
            ->andReturn([$this->cross_ref_link_1]);

        $this->cross_ref_link_list_presenter_builder
            ->shouldReceive('buildForTarget')
            ->with([$this->cross_ref_link_1])
            ->once()
            ->andReturn($this->cross_reference_link_list);

        $this->cross_ref_collection->shouldReceive('getCrossReferencesBoth')->twice()->andReturn([]);
        $this->cross_ref_collection->shouldReceive('getCrossReferencesTarget')->times(3)->andReturn([$this->cross_ref_target_1]);
        $this->cross_ref_collection->shouldReceive('getCrossReferencesSource')->once()->andReturn([]);
        $this->cross_ref_collection->shouldReceive('getLabel')->once()->andReturn('Tracker');

        $presenter = $this->builder->build($this->cross_ref_collection, true);

        $this->assertEquals("Tracker", $presenter->nature_label);
        $this->assertEquals([$this->cross_reference_link_list], $presenter->cross_reference_link_list);
    }

    public function testItCallsSourceCrossReferenceLinkListWithLinkArray(): void
    {
        $this->cross_ref_link_collection_presenter_builder
            ->shouldReceive('build')
            ->with([$this->cross_ref_target_1], 'source', true)
            ->andReturn([$this->cross_ref_link_1]);

        $this->cross_ref_link_list_presenter_builder
            ->shouldReceive('buildForSource')
            ->with([$this->cross_ref_link_1])
            ->once()
            ->andReturn($this->cross_reference_link_list);

        $this->cross_ref_collection->shouldReceive('getCrossReferencesBoth')->twice()->andReturn([]);
        $this->cross_ref_collection->shouldReceive('getCrossReferencesTarget')->twice()->andReturn([]);
        $this->cross_ref_collection->shouldReceive('getCrossReferencesSource')->times(3)->andReturn([$this->cross_ref_target_1]);
        $this->cross_ref_collection->shouldReceive('getLabel')->once()->andReturn('Tracker');

        $presenter = $this->builder->build($this->cross_ref_collection, true);

        $this->assertEquals("Tracker", $presenter->nature_label);
        $this->assertEquals([$this->cross_reference_link_list], $presenter->cross_reference_link_list);
    }

    public function testItCallsBothCrossReferenceLinkListWithLinkArrayAndWithinParams(): void
    {
        $this->cross_ref_link_collection_presenter_builder
            ->shouldReceive('build')
            ->with([$this->cross_ref_target_1], 'both', false)
            ->andReturn([$this->cross_ref_link_1]);

        $this->cross_ref_link_list_presenter_builder
            ->shouldReceive('buildForBoth')
            ->with([$this->cross_ref_link_1])
            ->once()
            ->andReturn($this->cross_reference_link_list);

        $this->cross_ref_collection->shouldReceive('getCrossReferencesBoth')->times(3)->andReturn([$this->cross_ref_target_1]);
        $this->cross_ref_collection->shouldReceive('getCrossReferencesTarget')->once()->andReturn([]);
        $this->cross_ref_collection->shouldReceive('getCrossReferencesSource')->once()->andReturn([]);
        $this->cross_ref_collection->shouldReceive('getLabel')->once()->andReturn('Tracker');

        $presenter = $this->builder->build($this->cross_ref_collection, false);

        $this->assertEquals("Tracker", $presenter->nature_label);
        $this->assertEquals([$this->cross_reference_link_list], $presenter->cross_reference_link_list);
    }

    public function testItCallsSourceAndBothAndTargetCrossReferenceLinkListWithLinkArray(): void
    {
        $this->cross_ref_link_collection_presenter_builder
            ->shouldReceive('build')
            ->with([$this->cross_ref_target_1], 'source', true)
            ->andReturn([$this->cross_ref_link_1]);

        $this->cross_ref_link_collection_presenter_builder
            ->shouldReceive('build')
            ->with([$this->cross_ref_target_2], 'target', true)
            ->andReturn([$this->cross_ref_link_2]);

        $this->cross_ref_link_collection_presenter_builder
            ->shouldReceive('build')
            ->with([$this->cross_ref_target_3], 'both', true)
            ->andReturn([$this->cross_ref_link_3]);

        $this->cross_ref_link_list_presenter_builder
            ->shouldReceive('buildForSource')
            ->with([$this->cross_ref_link_1])
            ->andReturn($this->cross_reference_link_list);

        $this->cross_ref_link_list_presenter_builder
            ->shouldReceive('buildForTarget')
            ->with([$this->cross_ref_link_2])
            ->andReturn($this->cross_reference_link_list_2);

        $this->cross_ref_link_list_presenter_builder
            ->shouldReceive('buildForBoth')
            ->with([$this->cross_ref_link_3])
            ->andReturn($this->cross_reference_link_list_3);

        $this->cross_ref_collection->shouldReceive('getCrossReferencesBoth')->times(3)->andReturn([$this->cross_ref_target_3]);
        $this->cross_ref_collection->shouldReceive('getCrossReferencesTarget')->twice()->andReturn([$this->cross_ref_target_2]);
        $this->cross_ref_collection->shouldReceive('getCrossReferencesSource')->twice()->andReturn([$this->cross_ref_target_1]);
        $this->cross_ref_collection->shouldReceive('getLabel')->once()->andReturn('Tracker');

        $presenter = $this->builder->build($this->cross_ref_collection, true);

        $this->assertEquals("Tracker", $presenter->nature_label);
        $this->assertEquals([$this->cross_reference_link_list, $this->cross_reference_link_list_2, $this->cross_reference_link_list_3], $presenter->cross_reference_link_list);
    }
}
