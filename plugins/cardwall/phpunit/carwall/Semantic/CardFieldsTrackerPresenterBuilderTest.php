<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Cardwall\Semantic;

require_once __DIR__ . '/../../../include/cardwallPlugin.class.php';
require_once __DIR__ . '/../../../../tracker/include/trackerPlugin.class.php';
require_once __DIR__ . '/../../../../tracker/tests/builders/aField.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_List_Bind;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_Users;
use Tracker_FormElement_Field_List_BindDecorator;
use Tracker_FormElementFactory;

class CardFieldsTrackerPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var BackgroundColorDao
     */
    private $background_color_dao;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var  BackgroundColorPresenterBuilder
     */
    private $builder;

    public function setUp()
    {
        parent::setUp();

        $this->form_element_factory = Mockery::mock(Tracker_FormElementFactory::class);
        $this->background_color_dao = Mockery::mock(BackgroundColorDao::class);
        $this->builder              = new BackgroundColorPresenterBuilder(
            $this->form_element_factory,
            $this->background_color_dao
        );
    }

    public function testItBuildAnArrayOfStaticListField()
    {
        $selectbox_bind = Mockery::mock(Tracker_FormElement_Field_List_Bind::class);
        $selectbox_bind->shouldReceive('getType')->andReturn(Tracker_FormElement_Field_List_Bind_Static::TYPE);

        $user_bind = Mockery::mock(Tracker_FormElement_Field_List_Bind::class);
        $user_bind->shouldReceive('getType')->andReturn(Tracker_FormElement_Field_List_Bind_Users::TYPE);

        $color_bind = Mockery::mock(Tracker_FormElement_Field_List_Bind::class);
        $color_bind->shouldReceive('getType')->andReturn(Tracker_FormElement_Field_List_Bind_Static::TYPE);

        $tracker_fields = [
            aSelectBoxField()->withId(100)->withLabel('selectbox')->withBind($selectbox_bind)->build(),
            aStringField()->withId(101)->withLabel('string')->build(),
            aSelectBoxField()->withId(102)->withLabel('userlist')->withBind($user_bind)->build(),
            aSelectBoxField()->withId(103)->withLabel('oldpalet')->withBind($color_bind)->build(),
        ];

        $selectbox_decorator   = new Tracker_FormElement_Field_List_BindDecorator(100, 1, null, null, null);
        $new_palette_decorator = new Tracker_FormElement_Field_List_BindDecorator(103, 2, null, null, null);
        $old_palette_decorator = new Tracker_FormElement_Field_List_BindDecorator(103, 2, 255, 255, 255);

        $selectbox_bind->shouldReceive('getDecorators')->andReturn([$selectbox_decorator]);
        $user_bind->shouldReceive('getDecorators')->andReturn([]);
        $color_bind->shouldReceive('getDecorators')->andReturn([$new_palette_decorator, $old_palette_decorator]);

        $this->form_element_factory->shouldReceive('getType')->andReturn('sb', 'string', 'sb');

        $this->background_color_dao->shouldReceive('searchBackgroundColor')->andReturn([]);

        $background_color_presenter = $this->builder->build($tracker_fields, aTracker()->withId(36)->build());

        $export_formatted_field_values = new BackgroundColorSelectorPresenter(
            [
                ['id' => 100, 'name' => 'selectbox', 'is_selected' => false]
            ]
        );

        $this->assertEquals($export_formatted_field_values, $background_color_presenter);
    }
}
