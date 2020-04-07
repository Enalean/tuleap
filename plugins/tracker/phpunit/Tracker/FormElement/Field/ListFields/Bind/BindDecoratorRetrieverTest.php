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

use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_BindDecorator;
use Tuleap\Tracker\Artifact\Exception\NoChangesetException;
use Tuleap\Tracker\Artifact\Exception\NoChangesetValueException;

class BindDecoratorRetrieverTest extends TestCase
{
    /** @var BindDecoratorRetriever */
    private $decorator_retriever;
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

    public function setUp(): void
    {
        parent::setUp();

        $this->decorator_retriever = new BindDecoratorRetriever();
        $this->list_field          = $this->createMock(Tracker_FormElement_Field_List::class);
        $this->artifact            = $this->createMock(Tracker_Artifact::class);
        $this->changeset           = $this->createMock(Tracker_Artifact_Changeset::class);
        $this->changeset->method('getId')->willReturn(747);
        $this->decorator           = $this->createMock(Tracker_FormElement_Field_List_BindDecorator::class);
        $this->bind_static         = $this->createMock(Tracker_FormElement_Field_List_Bind_Static::class);
    }

    public function testItReturnsTheDecoratorForTheFirstValue()
    {
        $values     = [['id' => 538]];
        $decorators = [538 => $this->decorator];

        $this->artifact->expects($this->once())->method('getLastChangeset')->willReturn($this->changeset);
        $this->list_field->expects($this->once())->method('getDecorators')->willReturn($decorators);
        $this->list_field->expects($this->once())->method('getBind')->willReturn($this->bind_static);
        $this->bind_static->expects($this->once())->method('getChangesetValues')->willReturn($values);
        $this->decorator->tlp_color_name = 'plum_crazy';

        $result = $this->decorator_retriever->getDecoratorForFirstValue($this->list_field, $this->artifact);

        $this->assertEquals($this->decorator, $result);
    }

    public function testItThrowsWhenNoDecorator()
    {
        $values = [['id' => 4884]];

        $this->artifact->method('getLastChangeset')->willReturn($this->changeset);
        $this->list_field->method('getDecorators')->willReturn([]);
        $this->list_field->method('getBind')->willReturn($this->bind_static);
        $this->bind_static->method('getChangesetValues')->willReturn($values);

        $this->expectException(NoBindDecoratorException::class);

        $this->decorator_retriever->getDecoratorForFirstValue($this->list_field, $this->artifact);
    }

    public function testItThrowsWhenNoValue()
    {
        $this->artifact->method('getLastChangeset')->willReturn($this->changeset);
        $this->list_field->method('getBind')->willReturn($this->bind_static);
        $this->bind_static->expects($this->once())->method('getChangesetValues')->with(747)->willReturn([]);

        $this->expectException(NoChangesetValueException::class);

        $this->decorator_retriever->getDecoratorForFirstValue($this->list_field, $this->artifact);
    }

    public function testItThrowsWhenNoChangeset()
    {
        $this->expectException(NoChangesetException::class);

        $this->decorator_retriever->getDecoratorForFirstValue($this->list_field, $this->artifact);
    }
}
