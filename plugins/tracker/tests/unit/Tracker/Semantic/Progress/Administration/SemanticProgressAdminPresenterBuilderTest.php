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
use Tuleap\Tracker\Semantic\Progress\InvalidMethod;
use Tuleap\Tracker\Semantic\Progress\MethodBasedOnEffort;
use Tuleap\Tracker\Semantic\Progress\MethodBasedOnLinksCount;
use Tuleap\Tracker\Semantic\Progress\MethodNotConfigured;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressDao;

class SemanticProgressAdminPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
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
                ['id' => 3, 'label' => 'Total effort', 'is_selected' => false],
            ],
            $presenter->total_effort_options
        );

        $this->assertSame(
            [
                ['id' => 1, 'label' => 'Velocity', 'is_selected' => false],
                ['id' => 2, 'label' => 'Remaining effort', 'is_selected' => false],
                ['id' => 3, 'label' => 'Total effort', 'is_selected' => false],
            ],
            $presenter->remaining_effort_options
        );

        $this->assertSame(
            [
                ['name' => MethodBasedOnEffort::getMethodName(), 'label' => MethodBasedOnEffort::getMethodLabel(), 'is_selected' => false],
                ['name' => MethodBasedOnLinksCount::getMethodName(), 'label' => MethodBasedOnLinksCount::getMethodLabel(), 'is_selected' => false],
            ],
            $presenter->available_computation_methods
        );

        $this->assertTrue($presenter->has_a_link_field);
    }

    public function testItBuildsTheAdministrationPresenterForAnInvalidSemantic(): void
    {
        $this->mockFormElementFactory(false);

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
                ['id' => 3, 'label' => 'Total effort', 'is_selected' => false],
            ],
            $presenter->total_effort_options
        );

        $this->assertSame(
            [
                ['id' => 1, 'label' => 'Velocity', 'is_selected' => false],
                ['id' => 2, 'label' => 'Remaining effort', 'is_selected' => false],
                ['id' => 3, 'label' => 'Total effort', 'is_selected' => false],
            ],
            $presenter->remaining_effort_options
        );

        $this->assertSame(
            [
                ['name' => MethodBasedOnEffort::getMethodName(), 'label' => MethodBasedOnEffort::getMethodLabel(), 'is_selected' => false],
                ['name' => MethodBasedOnLinksCount::getMethodName(), 'label' => MethodBasedOnLinksCount::getMethodLabel(), 'is_selected' => false],
            ],
            $presenter->available_computation_methods
        );

        $this->assertFalse($presenter->has_a_link_field);
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
                ['id' => 3, 'label' => 'Total effort', 'is_selected' => true],
            ],
            $presenter->total_effort_options
        );

        $this->assertSame(
            [
                ['id' => 1, 'label' => 'Velocity', 'is_selected' => false],
                ['id' => 2, 'label' => 'Remaining effort', 'is_selected' => true],
                ['id' => 3, 'label' => 'Total effort', 'is_selected' => false],
            ],
            $presenter->remaining_effort_options
        );

        $this->assertSame(
            [
                ['name' => MethodBasedOnEffort::getMethodName(), 'label' => MethodBasedOnEffort::getMethodLabel(), 'is_selected' => true],
                ['name' => MethodBasedOnLinksCount::getMethodName(), 'label' => MethodBasedOnLinksCount::getMethodLabel(), 'is_selected' => false],
            ],
            $presenter->available_computation_methods
        );

        $this->assertTrue($presenter->has_a_link_field);
    }

    public function testItBuildsTheAdministrationPresenterForALinksCountBasedSemantic(): void
    {
        $this->mockFormElementFactory();

        $presenter = $this->builder->build(
            $this->tracker,
            "Used in the Roadmap widget",
            false,
            "url/to/updater",
            \Mockery::mock(\CSRFSynchronizerToken::class),
            new MethodBasedOnLinksCount(
                \Mockery::mock(SemanticProgressDao::class),
                '_is_child'
            )
        );

        $this->assertSame(
            [
                ['name' => MethodBasedOnEffort::getMethodName(), 'label' => MethodBasedOnEffort::getMethodLabel(), 'is_selected' => false],
                ['name' => MethodBasedOnLinksCount::getMethodName(), 'label' => MethodBasedOnLinksCount::getMethodLabel(), 'is_selected' => true],
            ],
            $presenter->available_computation_methods
        );

        $this->assertTrue($presenter->has_a_link_field);
    }

    private function getNumericFieldMock(int $id, string $label)
    {
        return \Mockery::mock(
            \Tracker_FormElement_Field_Numeric::class,
            [
                'getId' => $id,
                'getLabel' => $label,
            ]
        );
    }

    private function mockFormElementFactory(bool $has_a_links_field = true): void
    {
        $links_fields = $has_a_links_field ? [\Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)] : [];

        $this->form_element_factory->shouldReceive('getUsedArtifactLinkFields')
            ->with($this->tracker)
            ->andReturn($links_fields);

        $this->form_element_factory->shouldReceive('getUsedFormElementsByType')
            ->with(
                $this->tracker,
                ['int', 'float', 'computed']
            )->andReturn(
                [
                    $this->getNumericFieldMock(1, "Velocity"),
                    $this->getNumericFieldMock(2, "Remaining effort"),
                    $this->getNumericFieldMock(3, "Total effort"),
                ]
            )->once();
    }
}
