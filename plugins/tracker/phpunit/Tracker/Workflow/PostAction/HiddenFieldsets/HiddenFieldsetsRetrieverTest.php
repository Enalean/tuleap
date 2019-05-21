<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Container_Fieldset;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\NoFrozenFieldsPostActionException;

class HiddenFieldsetsRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Mockery\MockInterface */
    private $hidden_dao;

    /** @var HiddenFieldsetsRetriever */
    private $hidden_fieldsets_retriever;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    protected function setUp(): void
    {
        $this->hidden_dao           = Mockery::mock(HiddenFieldsetsDao::class);
        $this->form_element_factory = Mockery::mock(\Tracker_FormElementFactory::class);

        $this->hidden_fieldsets_retriever = new HiddenFieldsetsRetriever(
            $this->hidden_dao,
            $this->form_element_factory
        );
    }

    public function testGetHiddenFieldsetsReturnsASinglePostAction()
    {
        $this->hidden_dao->shouldReceive('searchByTransitionId')->andReturn(
            [
                ['postaction_id' => 72, 'fieldset_id' => 331],
                ['postaction_id' => 72, 'fieldset_id' => 651],
            ]
        );

        $fieldset_01 = Mockery::mock(Tracker_FormElement_Container_Fieldset::class);
        $fieldset_02 = Mockery::mock(Tracker_FormElement_Container_Fieldset::class);

        $this->form_element_factory->shouldReceive('getFieldsetById')->with(331)->andReturn($fieldset_01);
        $this->form_element_factory->shouldReceive('getFieldsetById')->with(651)->andReturn($fieldset_02);

        $transition           = Mockery::mock(\Transition::class)->shouldReceive(['getId' => 97])->getMock();
        $expected_post_action = new HiddenFieldsets($transition, 72, [$fieldset_01, $fieldset_02]);

        $result = $this->hidden_fieldsets_retriever->getHiddenFieldsets($transition);

        $this->assertEquals($expected_post_action, $result);
    }

    public function testGetHiddenFieldsetsThrowsWhenNoPostAction()
    {
        $this->hidden_dao->shouldReceive('searchByTransitionId')->andReturn([]);

        $transition = Mockery::mock(\Transition::class)->shouldReceive(['getId' => 97])->getMock();

        $this->expectException(NoHiddenFieldsetsPostActionException::class);
        $this->hidden_fieldsets_retriever->getHiddenFieldsets($transition);
    }
}
