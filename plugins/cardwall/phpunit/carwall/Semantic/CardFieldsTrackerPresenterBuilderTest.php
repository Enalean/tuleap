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
use Tracker_FormElementFactory;

class CardFieldsTrackerPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var  CardFieldsTrackerPresenterBuilder
     */
    private $builder;

    public function setUp()
    {
        parent::setUp();

        $this->form_element_factory = Mockery::mock(Tracker_FormElementFactory::class);
        $this->builder              = new CardFieldsTrackerPresenterBuilder($this->form_element_factory);
    }

    public function testItBuildAnArrayOfStaticListField()
    {
        $selectbox_bind = Mockery::mock(Tracker_FormElement_Field_List_Bind::class);
        $selectbox_bind->shouldReceive('getType')->andReturn(Tracker_FormElement_Field_List_Bind_Static::TYPE);

        $user_bind = Mockery::mock(Tracker_FormElement_Field_List_Bind::class);
        $user_bind->shouldReceive('getType')->andReturn(Tracker_FormElement_Field_List_Bind_Users::TYPE);

        $tracker_fields = [
            aSelectBoxField()->withId(100)->withLabel('selectbox')->withBind($selectbox_bind)->build(),
            aStringField()->withId(101)->withLabel('string')->build(),
            aSelectBoxField()->withId(102)->withLabel('userlist')->withBind($user_bind)->build()
        ];

        $this->form_element_factory->shouldReceive('getType')->andReturn('sb', 'string', 'sb');

        $formatted_fields = $this->builder->getTrackerFields($tracker_fields);

        $export_formatted_field_values = [
            ['id' => 100, 'name' => 'selectbox']
        ];

        $this->assertEquals($export_formatted_field_values, $formatted_fields);
    }
}
