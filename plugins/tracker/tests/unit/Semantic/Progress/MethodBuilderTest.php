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

namespace Tuleap\Tracker\Semantic\Progress;

use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tracker;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MethodBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SemanticProgressDao&MockObject $dao;
    private Tracker_FormElementFactory&MockObject $form_element_factory;
    private MethodBuilder $method_builder;
    private \Tracker $tracker;
    private Project $project;
    private TypePresenterFactory&MockObject $natures_factory;

    protected function setUp(): void
    {
        $this->dao                  = $this->createMock(SemanticProgressDao::class);
        $this->form_element_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $this->natures_factory      = $this->createMock(TypePresenterFactory::class);
        $this->method_builder       = new MethodBuilder(
            $this->form_element_factory,
            $this->dao,
            $this->natures_factory
        );

        $this->project = ProjectTestBuilder::aProject()->build();
        $this->tracker = TrackerTestBuilder::aTracker()->withName('User Stories')->withProject($this->project)->build();
    }

    public function testItBuildsAnInvalidMethodWhenTotalAndRemainingEffortFieldsAreTheSameField(): void
    {
        $method = $this->method_builder->buildMethodBasedOnEffort(
            $this->tracker,
            1001,
            1001
        );

        $this->assertInstanceOf(
            InvalidMethod::class,
            $method
        );
    }

    public function testItBuildsAnInvalidMethodWhenTotalEffortFieldIdTargetsANonNumericField(): void
    {
        $this->form_element_factory
            ->method('getUsedFieldByIdAndType')
            ->willReturnCallback(
                fn (Tracker $tracker, int $field_id, mixed $type) => match ($field_id) {
                    1001 => $this->createMock(\Tracker_FormElement_Field_Date::class),
                    1002 => $this->createMock(\Tracker_FormElement_Field_Numeric::class),
                }
            );

        $method = $this->method_builder->buildMethodBasedOnEffort(
            $this->tracker,
            1001,
            1002
        );

        $this->assertInstanceOf(
            InvalidMethod::class,
            $method
        );
    }

    public function testItBuildsAnInvalidMethodWhenRemainingEffortFieldIdTargetsANonNumericField(): void
    {
        $this->form_element_factory
            ->method('getUsedFieldByIdAndType')
            ->willReturnCallback(
                fn (Tracker $tracker, int $field_id, mixed $type) => match ($field_id) {
                    1001 => $this->createMock(\Tracker_FormElement_Field_Numeric::class),
                    1002 => $this->createMock(\Tracker_FormElement_Field_Date::class),
                }
            );

        $method = $this->method_builder->buildMethodBasedOnEffort(
            $this->tracker,
            1001,
            1002
        );

        $this->assertInstanceOf(
            InvalidMethod::class,
            $method
        );
    }

    public function testItBuildsAMethodBasedOnEffort(): void
    {
        $this->form_element_factory
            ->method('getUsedFieldByIdAndType')
            ->willReturnCallback(
                fn (Tracker $tracker, int $field_id, mixed $type) => match ($field_id) {
                    1001 => $this->createMock(\Tracker_FormElement_Field_Numeric::class),
                    1002 => $this->createMock(\Tracker_FormElement_Field_Numeric::class),
                }
            );

        $method = $this->method_builder->buildMethodBasedOnEffort(
            $this->tracker,
            1001,
            1002
        );

        $this->assertInstanceOf(
            MethodBasedOnEffort::class,
            $method
        );
    }

    public function testItBuildsAMethodBasedOnEffortFromRequest(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParams([
                'computation-method' => MethodBasedOnEffort::getMethodName(),
                'total-effort-field-id' => '1001',
                'remaining-effort-field-id' => '1002',
            ])->build();

        $this->form_element_factory
            ->method('getUsedFieldByIdAndType')
            ->willReturnCallback(
                fn (Tracker $tracker, int $field_id, mixed $type) => match ($field_id) {
                    1001 => $this->createMock(\Tracker_FormElement_Field_Numeric::class),
                    1002 => $this->createMock(\Tracker_FormElement_Field_Numeric::class),
                }
            );

        $method = $this->method_builder->buildMethodFromRequest(
            $this->tracker,
            $request
        );

        $this->assertInstanceOf(
            MethodBasedOnEffort::class,
            $method
        );
    }

    public function testItBuildsAMethodBasedOnLinksCountFromRequest(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParams([
                'computation-method' => MethodBasedOnLinksCount::getMethodName(),
            ])->build();

        $this->form_element_factory
            ->expects($this->once())
            ->method('getUsedArtifactLinkFields')
            ->with($this->tracker)
            ->willReturn([
                $this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class),
            ]);

        $this->natures_factory
            ->expects($this->once())
            ->method('getTypeEnabledInProjectFromShortname')
            ->with($this->project, '_is_child')
            ->willReturn(
                new TypePresenter('_is_child', 'Parent', 'Child', true)
            );

        $method = $this->method_builder->buildMethodFromRequest(
            $this->tracker,
            $request
        );

        $this->assertInstanceOf(
            MethodBasedOnLinksCount::class,
            $method
        );
    }

    public function testItBuildsAnInvalidMethodFromRequestWhenTotalEffortIdIsMissing(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParams([
                'computation-method' => MethodBasedOnEffort::getMethodName(),
                'remaining-effort-field-id' => '1002',
            ])->build();

        $this->form_element_factory
            ->method('getUsedFieldByIdAndType')
            ->willReturnCallback(
                fn (Tracker $tracker, int $field_id, mixed $type) => match ($field_id) {
                    0 => null,
                    1002 => $this->createMock(\Tracker_FormElement_Field_Numeric::class),
                }
            );

        $method = $this->method_builder->buildMethodFromRequest(
            $this->tracker,
            $request
        );

        $this->assertInstanceOf(
            InvalidMethod::class,
            $method
        );
    }

    public function testItBuildsAnInvalidMethodFromRequestWhenRemainingEffortIdIsMissing(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParams([
                'computation-method' => MethodBasedOnEffort::getMethodName(),
                'total-effort-field-id' => '1001',
            ])->build();

        $this->form_element_factory
            ->method('getUsedFieldByIdAndType')
            ->willReturnCallback(
                fn (Tracker $tracker, int $field_id, mixed $type) => match ($field_id) {
                    0 => null,
                    1001 => $this->createMock(\Tracker_FormElement_Field_Numeric::class),
                }
            );

        $method = $this->method_builder->buildMethodFromRequest(
            $this->tracker,
            $request
        );

        $this->assertInstanceOf(
            InvalidMethod::class,
            $method
        );
    }

    public function testItBuildsAnInvalidMethodFromRequestWhenABadMethodNameIsProvided(): void
    {
        $request = $this->createMock(\Codendi_Request::class);
        $request->method('get')->with('computation-method')->willReturn('random-float');

        $method = $this->method_builder->buildMethodFromRequest(
            $this->tracker,
            $request
        );

        $this->assertInstanceOf(
            InvalidMethod::class,
            $method
        );
    }

    public function testItBuildsAMethodBasedOnChildCount(): void
    {
        $this->form_element_factory
            ->expects($this->once())
            ->method('getUsedArtifactLinkFields')
            ->with($this->tracker)
            ->willReturn([
                $this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class),
            ]);

        $this->natures_factory
            ->expects($this->once())
            ->method('getTypeEnabledInProjectFromShortname')
            ->with($this->project, '_is_child')
            ->willReturn(
                new TypePresenter('_is_child', 'Parent', 'Child', true)
            );

        $method = $this->method_builder->buildMethodBasedOnChildCount(
            $this->tracker,
            '_is_child',
        );

        $this->assertInstanceOf(
            MethodBasedOnLinksCount::class,
            $method
        );
    }

    public function testItBuildsAnInvalidMethodWhenThereIsNoArtifactLinkFieldInTracker(): void
    {
        $this->form_element_factory
            ->expects($this->once())
            ->method('getUsedArtifactLinkFields')
            ->with($this->tracker)
            ->willReturn([]);

        $method = $this->method_builder->buildMethodBasedOnChildCount(
            $this->tracker,
            '_is_child',
        );

        $this->assertInstanceOf(
            InvalidMethod::class,
            $method
        );
    }

    public function testItBuildsAnInvalidMethodWhenLinkNatureIsNotIsChild(): void
    {
        $this->form_element_factory
            ->expects($this->once())
            ->method('getUsedArtifactLinkFields')
            ->with($this->tracker)
            ->willReturn([
                $this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class),
            ]);

        $method = $this->method_builder->buildMethodBasedOnChildCount(
            $this->tracker,
            'delivered_in',
        );

        $this->assertInstanceOf(
            InvalidMethod::class,
            $method
        );
    }

    public function testItBuildsAnInvalidMethodWhenLinkNatureIsChildIsNotEnabledInProject(): void
    {
        $this->form_element_factory
            ->expects($this->once())
            ->method('getUsedArtifactLinkFields')
            ->with($this->tracker)
            ->willReturn([
                $this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class),
            ]);

        $this->natures_factory
            ->expects($this->once())
            ->method('getTypeEnabledInProjectFromShortname')
            ->with($this->project, '_is_child')
            ->willReturn(null);

        $method = $this->method_builder->buildMethodBasedOnChildCount(
            $this->tracker,
            '_is_child',
        );

        $this->assertInstanceOf(
            InvalidMethod::class,
            $method
        );
    }
}
