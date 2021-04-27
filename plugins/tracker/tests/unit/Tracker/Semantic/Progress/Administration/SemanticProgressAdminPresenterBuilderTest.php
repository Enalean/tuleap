<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Progress\Administration;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Semantic\Progress\InvalidMethod;
use Tuleap\Tracker\Semantic\Progress\MethodBasedOnEffort;
use Tuleap\Tracker\Semantic\Progress\MethodNotConfigured;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressDao;

class SemanticProgressAdminPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var SemanticProgressAdminPresenterBuilder
     */
    private $builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker
     */
    private $tracker;

    protected function setUp(): void
    {
        $this->tracker              = \Mockery::mock(\Tracker::class, ['getId' => 113]);
        $this->form_element_factory = \Mockery::mock(\Tracker_FormElementFactory::class);
        $this->builder              = new SemanticProgressAdminPresenterBuilder(
            $this->form_element_factory
        );
    }

    public function testItBuildsTheAdministrationPresenterForANotConfiguredSemantic(): void
    {
        $this->mockFormElementFactory();

        $presenter = $this->builder->build(
            $this->tracker,
            "Used in the Roadmap widget",
            false,
            "url/to/updater",
            \Mockery::mock(\CSRFSynchronizerToken::class),
            new MethodNotConfigured()
        );

        $this->assertSame(
            [
                ['id' => 1, 'label' => 'Velocity', 'is_selected' => false],
                ['id' => 2, 'label' => 'Remaining effort', 'is_selected' => false],
                ['id' => 3, 'label' => 'Total effort', 'is_selected' => false]
            ],
            $presenter->total_effort_options
        );

        $this->assertSame(
            [
                ['id' => 1, 'label' => 'Velocity', 'is_selected' => false],
                ['id' => 2, 'label' => 'Remaining effort', 'is_selected' => false],
                ['id' => 3, 'label' => 'Total effort', 'is_selected' => false]
            ],
            $presenter->remaining_effort_options
        );

        $this->assertSame(
            [
                ['name' => MethodBasedOnEffort::getMethodName(), 'label' => 'Effort based', 'is_selected' => false],
            ],
            $presenter->available_computation_methods
        );
    }

    public function testItBuildsTheAdministrationPresenterForAnInvalidSemantic(): void
    {
        $this->mockFormElementFactory();

        $presenter = $this->builder->build(
            $this->tracker,
            "Used in the Roadmap widget",
            false,
            "url/to/updater",
            \Mockery::mock(\CSRFSynchronizerToken::class),
            new InvalidMethod('This is broken')
        );

        $this->assertSame(
            [
                ['id' => 1, 'label' => 'Velocity', 'is_selected' => false],
                ['id' => 2, 'label' => 'Remaining effort', 'is_selected' => false],
                ['id' => 3, 'label' => 'Total effort', 'is_selected' => false]
            ],
            $presenter->total_effort_options
        );

        $this->assertSame(
            [
                ['id' => 1, 'label' => 'Velocity', 'is_selected' => false],
                ['id' => 2, 'label' => 'Remaining effort', 'is_selected' => false],
                ['id' => 3, 'label' => 'Total effort', 'is_selected' => false]
            ],
            $presenter->remaining_effort_options
        );

        $this->assertSame(
            [
                ['name' => MethodBasedOnEffort::getMethodName(), 'label' => 'Effort based', 'is_selected' => false],
            ],
            $presenter->available_computation_methods
        );
    }

    public function testItBuildsTheAdministrationPresenterForAnEffortBasedSemantic(): void
    {
        $this->mockFormElementFactory();

        $presenter = $this->builder->build(
            $this->tracker,
            "Used in the Roadmap widget",
            false,
            "url/to/updater",
            \Mockery::mock(\CSRFSynchronizerToken::class),
            new MethodBasedOnEffort(
                \Mockery::mock(SemanticProgressDao::class),
                $this->getNumericFieldMock(3, "Total effort"),
                $this->getNumericFieldMock(2, "Remaining effort")
            )
        );

        $this->assertSame(
            [
                ['id' => 1, 'label' => 'Velocity', 'is_selected' => false],
                ['id' => 2, 'label' => 'Remaining effort', 'is_selected' => false],
                ['id' => 3, 'label' => 'Total effort', 'is_selected' => true]
            ],
            $presenter->total_effort_options
        );

        $this->assertSame(
            [
                ['id' => 1, 'label' => 'Velocity', 'is_selected' => false],
                ['id' => 2, 'label' => 'Remaining effort', 'is_selected' => true],
                ['id' => 3, 'label' => 'Total effort', 'is_selected' => false]
            ],
            $presenter->remaining_effort_options
        );

        $this->assertSame(
            [
                ['name' => MethodBasedOnEffort::getMethodName(), 'label' => 'Effort based', 'is_selected' => true],
            ],
            $presenter->available_computation_methods
        );
    }

    private function getNumericFieldMock(int $id, string $label)
    {
        return \Mockery::mock(
            \Tracker_FormElement_Field_Numeric::class,
            [
                'getId' => $id,
                'getLabel' => $label
            ]
        );
    }

    private function mockFormElementFactory(): void
    {
        $this->form_element_factory->shouldReceive('getUsedFormElementsByType')
            ->with(
                $this->tracker,
                ['int', 'float', 'computed']
            )->andReturn(
                [
                    $this->getNumericFieldMock(1, "Velocity"),
                    $this->getNumericFieldMock(2, "Remaining effort"),
                    $this->getNumericFieldMock(3, "Total effort")
                ]
            )->once();
    }
}
