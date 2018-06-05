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

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind;

require_once __DIR__ . '/../../../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_BindDecorator;

class BindDecoratorColorRetrieverTest extends TestCase
{
    /** @var BindDecoratorColorRetriever */
    private $color_retriever;
    /** @var Tracker_FormElement_Field_List */
    private $list_field;
    /** @var Tracker_Artifact */
    private $artifact;
    /** @var Tracker_Artifact_Changeset */
    private $changeset;
    /** @var Tracker_FormElement_Field_List_BindDecorator */
    private $decorator;
    /** @var Tracker_FormElement_Field_List_Bind_Static */
    private $bind_static;

    public function setUp()
    {
        parent::setUp();

        $this->color_retriever = new BindDecoratorColorRetriever();
        $this->list_field      = $this->createMock(Tracker_FormElement_Field_List::class);
        $this->artifact        = $this->createMock(Tracker_Artifact::class);
        $this->changeset       = $this->createMock(Tracker_Artifact_Changeset::class);
        $this->changeset->method('getId')->willReturn(106);
        $this->decorator   = $this->createMock(Tracker_FormElement_Field_List_BindDecorator::class);
        $this->bind_static = $this->createMock(Tracker_FormElement_Field_List_Bind_Static::class);
    }

    public function testItRetrievesTheTLPColorName()
    {
        $values     = [['id' => 956]];
        $decorators = [956 => $this->decorator];

        $this->artifact->expects($this->once())->method('getLastChangeset')->willReturn($this->changeset);
        $this->list_field->expects($this->once())->method('getDecorators')->willReturn($decorators);
        $this->list_field->expects($this->once())->method('getBind')->willReturn($this->bind_static);
        $this->bind_static->expects($this->once())->method('getChangesetValues')->willReturn($values);
        $this->decorator->tlp_color_name = 'plum_crazy';

        $result = $this->color_retriever->getCurrentDecoratorColor($this->list_field, $this->artifact);

        $this->assertEquals('plum_crazy', $result);
    }

    public function testItReturnsTheColorOnlyForTheFirstSelectedValue()
    {
        $values           = [['id' => 504], ['id' => 295]];
        $second_decorator = $this->createMock(Tracker_FormElement_Field_List_BindDecorator::class);
        $decorators       = [504 => $this->decorator, 295 => $second_decorator];

        $this->artifact->method('getLastChangeset')->willReturn($this->changeset);
        $this->list_field->method('getDecorators')->willReturn($decorators);
        $this->list_field->method('getBind')->willReturn($this->bind_static);
        $this->bind_static->method('getChangesetValues')->willReturn($values);
        $this->decorator->tlp_color_name  = 'surf_green';
        $second_decorator->tlp_color_name = 'clockwork_orange';

        $result = $this->color_retriever->getCurrentDecoratorColor($this->list_field, $this->artifact);

        $this->assertEquals('surf_green', $result);
    }

    public function testItReturnsEmptyWhenNoDecorator()
    {
        $values = [['id' => 175]];

        $this->artifact->method('getLastChangeset')->willReturn($this->changeset);
        $this->list_field->method('getDecorators')->willReturn([]);
        $this->list_field->method('getBind')->willReturn($this->bind_static);
        $this->bind_static->method('getChangesetValues')->willReturn($values);

        $this->assertEquals('', $this->color_retriever->getCurrentDecoratorColor($this->list_field, $this->artifact));
    }

    public function testItReturnsEmptyWhenNoValue()
    {
        $this->artifact->method('getLastChangeset')->willReturn($this->changeset);
        $this->list_field->method('getBind')->willReturn($this->bind_static);
        $this->bind_static->expects($this->once())->method('getChangesetValues')->with(106)->willReturn([]);

        $this->assertEquals('', $this->color_retriever->getCurrentDecoratorColor($this->list_field, $this->artifact));
    }

    public function testItReturnsEmptyWhenNoChangeset()
    {
        $this->assertEquals('', $this->color_retriever->getCurrentDecoratorColor($this->list_field, $this->artifact));
    }
}
