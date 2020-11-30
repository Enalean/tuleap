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

namespace Tuleap\Tracker\FormElement\View\Reference;

use PHPUnit\Framework\TestCase;
use Tuleap\reference\CrossReferenceByNatureCollection;
use Tuleap\reference\CrossReferenceCollection;

class CrossReferenceFieldPresenterBuilderTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var CrossReferenceFieldPresenterBuilder
     */
    private $builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CrossReferenceByNatureCollection
     */
    private $cross_ref_by_nature_collection;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CrossReferenceByNaturePresenterBuilder
     */
    private $cross_ref_by_nature_presenter_builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CrossReferenceCollection
     */
    private $cross_ref_collection;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CrossReferenceByNaturePresenter
     */
    private $cross_ref_by_nature_presenter;

    protected function setUp(): void
    {
        $this->cross_ref_by_nature_presenter_builder = \Mockery::mock(CrossReferenceByNaturePresenterBuilder::class);
        $this->cross_ref_by_nature_collection = \Mockery::mock(CrossReferenceByNatureCollection::class);

        $this->cross_ref_collection = \Mockery::mock(CrossReferenceCollection::class);

        $this->cross_ref_by_nature_presenter = \Mockery::mock(CrossReferenceByNaturePresenter::class);

        $this->builder = new CrossReferenceFieldPresenterBuilder($this->cross_ref_by_nature_presenter_builder);
    }

    public function testItReturnsPresenterWithEmptyCrossRef(): void
    {
        $this->cross_ref_by_nature_collection->shouldReceive('getAll')->andReturn([]);
        $this->cross_ref_by_nature_presenter_builder->shouldNotReceive('build');
        $presenter = $this->builder->build($this->cross_ref_by_nature_collection, true);

        $this->assertEquals([], $presenter->cross_refs_by_nature);
        $this->assertEquals(true, $presenter->can_delete);
    }

    public function testItReturnsPresenterWithEmptyCrossRefIfNoCrossRefs(): void
    {
        $this->cross_ref_by_nature_collection->shouldReceive('getAll')->andReturn([$this->cross_ref_collection]);
        $this->cross_ref_by_nature_presenter_builder
            ->shouldReceive('build')
            ->with($this->cross_ref_collection)
            ->andReturnNull();

        $presenter = $this->builder->build($this->cross_ref_by_nature_collection, true);

        $this->assertEquals([], $presenter->cross_refs_by_nature);
        $this->assertEquals(true, $presenter->can_delete);
    }

    public function testItReturnsPresenterWithCrossRefs(): void
    {
        $this->cross_ref_by_nature_collection->shouldReceive('getAll')->andReturn([$this->cross_ref_collection]);
        $this->cross_ref_by_nature_presenter_builder
            ->shouldReceive('build')
            ->with($this->cross_ref_collection)
            ->andReturn($this->cross_ref_by_nature_presenter);

        $presenter = $this->builder->build($this->cross_ref_by_nature_collection, true);

        $this->assertEquals([$this->cross_ref_by_nature_presenter], $presenter->cross_refs_by_nature);
        $this->assertEquals(true, $presenter->can_delete);
    }
}
