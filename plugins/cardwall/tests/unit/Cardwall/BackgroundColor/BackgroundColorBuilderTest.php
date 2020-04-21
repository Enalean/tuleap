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

namespace Tuleap\Cardwall\BackgroundColor;

require_once __DIR__ . '/../../bootstrap.php';

use Cardwall_Semantic_CardFields;
use PFUser;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Tracker_Artifact;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_BindDecorator;
use Tuleap\Cardwall\Semantic\BackgroundColorSemanticFieldNotFoundException;
use Tuleap\Tracker\Artifact\Exception\NoChangesetException;
use Tuleap\Tracker\Artifact\Exception\NoChangesetValueException;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorRetriever;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\NoBindDecoratorException;

class BackgroundColorBuilderTest extends TestCase
{
    /** @var BackgroundColorBuilder */
    private $background_color_builder;
    /** @var PHPUnit_Framework_MockObject_MockObject */
    private $decorator_retriever;
    /** @var PHPUnit_Framework_MockObject_MockObject */
    private $current_user;
    /** @var PHPUnit_Framework_MockObject_MockObject */
    private $artifact;
    /** @var PHPUnit_Framework_MockObject_MockObject */
    private $card_fields_semantic;
    /** @var PHPUnit_Framework_MockObject_MockObject */
    private $decorator;
    /** @var PHPUnit_Framework_MockObject_MockObject */
    private $field;

    public function setUp(): void
    {
        parent::setUp();

        $this->card_fields_semantic = $this->createMock(Cardwall_Semantic_CardFields::class);
        $this->artifact = $this->createMock(Tracker_Artifact::class);
        $this->current_user = $this->createMock(PFUser::class);
        $this->decorator_retriever = $this->createMock(BindDecoratorRetriever::class);
        $this->field = $this->createMock(Tracker_FormElement_Field_List::class);
        $this->decorator = $this->createMock(Tracker_FormElement_Field_List_BindDecorator::class);
        $this->background_color_builder = new BackgroundColorBuilder($this->decorator_retriever);
    }

    public function testItBuildsABackgroundColor()
    {
        $this->card_fields_semantic->method('getBackgroundColorField')->willReturn($this->field);
        $this->field->method('userCanRead')->willReturn(true);
        $this->decorator_retriever->method('getDecoratorForFirstValue')->willReturn($this->decorator);
        $this->decorator->tlp_color_name = 'acid-green';

        $result = $this->background_color_builder->build(
            $this->card_fields_semantic,
            $this->artifact,
            $this->current_user
        );

        $this->assertEquals(new BackgroundColor('acid-green'), $result);
    }

    public function testItBuildsAnEmptyColorWhenNoDecorator()
    {
        $this->card_fields_semantic->method('getBackgroundColorField')->willReturn($this->field);
        $this->field->method('userCanRead')->willReturn(true);
        $this->decorator_retriever->method('getDecoratorForFirstValue')->willThrowException(
            new NoBindDecoratorException()
        );

        $result = $this->background_color_builder->build(
            $this->card_fields_semantic,
            $this->artifact,
            $this->current_user
        );

        $this->assertEquals(new BackgroundColor(''), $result);
    }

    public function testItBuildsAnEmptyColorWhenNoChangesetValue()
    {
        $this->card_fields_semantic->method('getBackgroundColorField')->willReturn($this->field);
        $this->field->method('userCanRead')->willReturn(true);
        $this->decorator_retriever->method('getDecoratorForFirstValue')->willThrowException(
            new NoChangesetValueException()
        );

        $result = $this->background_color_builder->build(
            $this->card_fields_semantic,
            $this->artifact,
            $this->current_user
        );

        $this->assertEquals(new BackgroundColor(''), $result);
    }

    public function testItBuildsAnEmptyColorWhenNoChangeset()
    {
        $this->card_fields_semantic->method('getBackgroundColorField')->willReturn($this->field);
        $this->field->method('userCanRead')->willReturn(true);
        $this->decorator_retriever->expects($this->once())->method('getDecoratorForFirstValue')->with(
            $this->field,
            $this->artifact
        )->willThrowException(new NoChangesetException());

        $result = $this->background_color_builder->build(
            $this->card_fields_semantic,
            $this->artifact,
            $this->current_user
        );

        $this->assertEquals(new BackgroundColor(''), $result);
    }

    public function testItBuildsAnEmptyColorWhenTheBackgroundFieldIsNotFound()
    {
        $this->card_fields_semantic->expects($this->once())->method('getBackgroundColorField')->willThrowException(
            new BackgroundColorSemanticFieldNotFoundException()
        );

        $result = $this->background_color_builder->build(
            $this->card_fields_semantic,
            $this->artifact,
            $this->current_user
        );

        $this->assertEquals(new BackgroundColor(''), $result);
    }

    public function testItBuildsAnEmptyColorWhenCurrentUserCantReadBackgroundField()
    {
        $this->card_fields_semantic->method('getBackgroundColorField')->willReturn($this->field);
        $this->field->expects($this->once())->method('userCanRead')->with($this->current_user)->willReturn(false);

        $result = $this->background_color_builder->build(
            $this->card_fields_semantic,
            $this->artifact,
            $this->current_user
        );

        $this->assertEquals(new BackgroundColor(''), $result);
    }
}
