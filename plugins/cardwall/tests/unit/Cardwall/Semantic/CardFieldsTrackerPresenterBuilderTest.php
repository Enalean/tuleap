<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Cardwall\Semantic;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field_List_BindDecorator;
use Tracker_FormElementFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CardFieldsTrackerPresenterBuilderTest extends TestCase
{
    private BackgroundColorDao&MockObject $background_color_dao;
    private Tracker_FormElementFactory&MockObject $form_element_factory;
    private BackgroundColorPresenterBuilder $builder;

    public function setUp(): void
    {
        $this->form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $this->background_color_dao = $this->createMock(BackgroundColorDao::class);
        $this->builder              = new BackgroundColorPresenterBuilder(
            $this->form_element_factory,
            $this->background_color_dao
        );
    }

    public function testItAddsTheFieldInPresentersWhenNoColorIsChosen(): void
    {
        $field = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(100)->withLabel('selectbox')->build()
        )
            ->withDecorators([new Tracker_FormElement_Field_List_BindDecorator(100, 1, null, null, null, null)])
            ->build()
            ->getField();

        $this->form_element_factory->method('getType')->willReturn('sb');
        $this->background_color_dao->method('searchBackgroundColor')->willReturn(false);

        $tracker                    = TrackerTestBuilder::aTracker()->withId(36)->build();
        $background_color_presenter = $this->builder->build([$field], $tracker);

        $export_formatted_field_values = new BackgroundColorSelectorPresenter(
            [
                ['id' => 100, 'name' => 'selectbox', 'is_selected' => false],
            ],
            false,
            ''
        );

        self::assertEquals($export_formatted_field_values, $background_color_presenter);
    }

    public function testItDoesNotAddDecoratorWhenFieldIsNotASelectBoxOrARadioButton(): void
    {
        $this->form_element_factory->method('getType')->willReturn('string');
        $this->background_color_dao->method('searchBackgroundColor')->willReturn(false);

        $tracker_fields             = [StringFieldBuilder::aStringField(103)->build()];
        $tracker                    = TrackerTestBuilder::aTracker()->withId(36)->build();
        $background_color_presenter = $this->builder->build($tracker_fields, $tracker);

        $export_formatted_field_values = new BackgroundColorSelectorPresenter([], false, '');

        self::assertEquals($export_formatted_field_values, $background_color_presenter);
    }

    public function testItDoesNotAddSelectboxFieldsWhenTheyAreNonStatic(): void
    {
        $field = ListUserBindBuilder::aUserBind(
            ListFieldBuilder::aListField(100)->withLabel('selectbox')->build()
        )->build()->getField();

        $this->form_element_factory->method('getType')->willReturn('sb');
        $this->background_color_dao->method('searchBackgroundColor')->willReturn(false);

        $tracker                    = TrackerTestBuilder::aTracker()->withId(36)->build();
        $background_color_presenter = $this->builder->build([$field], $tracker);

        $export_formatted_field_values = new BackgroundColorSelectorPresenter(
            [],
            false,
            ''
        );

        self::assertEquals($export_formatted_field_values, $background_color_presenter);
    }

    public function testItAddsTheFieldInPresentersWhenTheColorIsATlpColor(): void
    {
        $field = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(101)->withLabel('selectbox')->build()
        )
            ->withDecorators([new Tracker_FormElement_Field_List_BindDecorator(101, 2, null, null, null, 'fiesta-red')])
            ->build()
            ->getField();

        $this->form_element_factory->method('getType')->willReturn('sb');
        $this->background_color_dao->method('searchBackgroundColor')->willReturn(false);

        $tracker                    = TrackerTestBuilder::aTracker()->withId(36)->build();
        $background_color_presenter = $this->builder->build([$field], $tracker);

        $export_formatted_field_values = new BackgroundColorSelectorPresenter(
            [
                ['id' => 101, 'name' => 'selectbox', 'is_selected' => false],
            ],
            false,
            ''
        );

        self::assertEquals($export_formatted_field_values, $background_color_presenter);
    }

    public function testItDoesNotAddTheFieldInPresentersWhenTheColorIsALegacyColor(): void
    {
        $field = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(103)->withLabel('selectbox')->build()
        )
            ->withDecorators([new Tracker_FormElement_Field_List_BindDecorator(103, 2, 255, 255, 255, null)])
            ->build()
            ->getField();

        $this->form_element_factory->method('getType')->willReturn('sb');
        $this->background_color_dao->method('searchBackgroundColor')->willReturn(false);

        $tracker                    = TrackerTestBuilder::aTracker()->withId(36)->build();
        $background_color_presenter = $this->builder->build([$field], $tracker);

        $export_formatted_field_values = new BackgroundColorSelectorPresenter([], false, '');

        self::assertEquals($export_formatted_field_values, $background_color_presenter);
    }
}
