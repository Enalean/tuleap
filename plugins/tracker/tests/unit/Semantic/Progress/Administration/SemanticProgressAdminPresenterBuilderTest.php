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

use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Semantic\Progress\InvalidMethod;
use Tuleap\Tracker\Semantic\Progress\MethodBasedOnEffort;
use Tuleap\Tracker\Semantic\Progress\MethodBasedOnLinksCount;
use Tuleap\Tracker\Semantic\Progress\MethodNotConfigured;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressDao;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SemanticProgressAdminPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Tracker_FormElementFactory&MockObject $form_element_factory;
    private SemanticProgressAdminPresenterBuilder $builder;
    private Tracker $tracker;

    protected function setUp(): void
    {
        $this->tracker              = TrackerTestBuilder::aTracker()->build();
        $this->form_element_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $this->builder              = new SemanticProgressAdminPresenterBuilder(
            $this->form_element_factory
        );
    }

    public function testItBuildsTheAdministrationPresenterForANotConfiguredSemantic(): void
    {
        $this->mockFormElementFactory();

        $presenter = $this->builder->build(
            $this->tracker,
            'Used in the Roadmap widget',
            false,
            'url/to/updater',
            $this->createMock(\CSRFSynchronizerToken::class),
            new MethodNotConfigured()
        );

        self::assertSame(
            [
                ['id' => 1, 'label' => 'Velocity', 'is_selected' => false],
                ['id' => 2, 'label' => 'Remaining effort', 'is_selected' => false],
                ['id' => 3, 'label' => 'Total effort', 'is_selected' => false],
            ],
            $presenter->total_effort_options
        );

        self::assertSame(
            [
                ['id' => 1, 'label' => 'Velocity', 'is_selected' => false],
                ['id' => 2, 'label' => 'Remaining effort', 'is_selected' => false],
                ['id' => 3, 'label' => 'Total effort', 'is_selected' => false],
            ],
            $presenter->remaining_effort_options
        );

        self::assertSame(
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
            'Used in the Roadmap widget',
            false,
            'url/to/updater',
            $this->createMock(\CSRFSynchronizerToken::class),
            new InvalidMethod('This is broken')
        );

        self::assertSame(
            [
                ['id' => 1, 'label' => 'Velocity', 'is_selected' => false],
                ['id' => 2, 'label' => 'Remaining effort', 'is_selected' => false],
                ['id' => 3, 'label' => 'Total effort', 'is_selected' => false],
            ],
            $presenter->total_effort_options
        );

        self::assertSame(
            [
                ['id' => 1, 'label' => 'Velocity', 'is_selected' => false],
                ['id' => 2, 'label' => 'Remaining effort', 'is_selected' => false],
                ['id' => 3, 'label' => 'Total effort', 'is_selected' => false],
            ],
            $presenter->remaining_effort_options
        );

        self::assertSame(
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
            'Used in the Roadmap widget',
            false,
            'url/to/updater',
            $this->createMock(\CSRFSynchronizerToken::class),
            new MethodBasedOnEffort(
                $this->createMock(SemanticProgressDao::class),
                $this->getNumericField(3, 'Total effort'),
                $this->getNumericField(2, 'Remaining effort')
            )
        );

        self::assertSame(
            [
                ['id' => 1, 'label' => 'Velocity', 'is_selected' => false],
                ['id' => 2, 'label' => 'Remaining effort', 'is_selected' => false],
                ['id' => 3, 'label' => 'Total effort', 'is_selected' => true],
            ],
            $presenter->total_effort_options
        );

        self::assertSame(
            [
                ['id' => 1, 'label' => 'Velocity', 'is_selected' => false],
                ['id' => 2, 'label' => 'Remaining effort', 'is_selected' => true],
                ['id' => 3, 'label' => 'Total effort', 'is_selected' => false],
            ],
            $presenter->remaining_effort_options
        );

        self::assertSame(
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
            'Used in the Roadmap widget',
            false,
            'url/to/updater',
            $this->createMock(\CSRFSynchronizerToken::class),
            new MethodBasedOnLinksCount(
                $this->createMock(SemanticProgressDao::class),
                '_is_child'
            )
        );

        self::assertSame(
            [
                ['name' => MethodBasedOnEffort::getMethodName(), 'label' => MethodBasedOnEffort::getMethodLabel(), 'is_selected' => false],
                ['name' => MethodBasedOnLinksCount::getMethodName(), 'label' => MethodBasedOnLinksCount::getMethodLabel(), 'is_selected' => true],
            ],
            $presenter->available_computation_methods
        );

        $this->assertTrue($presenter->has_a_link_field);
    }

    private function getNumericField(int $id, string $label): \Tracker_FormElement_Field_Numeric
    {
        return IntFieldBuilder::anIntField($id)->withLabel($label)->build();
    }

    private function mockFormElementFactory(bool $has_a_links_field = true): void
    {
        $links_fields = $has_a_links_field ? [$this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class)] : [];

        $this->form_element_factory->method('getUsedArtifactLinkFields')
            ->with($this->tracker)
            ->willReturn($links_fields);

        $this->form_element_factory
            ->expects($this->once())
            ->method('getUsedFormElementsByType')
            ->with(
                $this->tracker,
                ['int', 'float', 'computed']
            )->willReturn(
                [
                    $this->getNumericField(1, 'Velocity'),
                    $this->getNumericField(2, 'Remaining effort'),
                    $this->getNumericField(3, 'Total effort'),
                ]
            );
    }
}
