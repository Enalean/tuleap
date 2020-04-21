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

namespace Tuleap\Cardwall\AccentColor;

require_once __DIR__ . '/../../bootstrap.php';

use PFUser;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Tracker;
use Tracker_Artifact;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Artifact\Exception\NoChangesetException;
use Tuleap\Tracker\Artifact\Exception\NoChangesetValueException;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorRetriever;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\NoBindDecoratorException;

class AccentColorBuilderTest extends TestCase
{
    /** @var AccentColorBuilder */
    private $accent_color_builder;
    /** @var PHPUnit_Framework_MockObject_MockObject */
    private $decorator_retriever;
    /** @var PHPUnit_Framework_MockObject_MockObject */
    private $current_user;
    /** @var PHPUnit_Framework_MockObject_MockObject */
    private $artifact;
    /** @var PHPUnit_Framework_MockObject_MockObject */
    private $form_element_factory;
    /** @var PHPUnit_Framework_MockObject_MockObject */
    private $selectbox;
    /** @var PHPUnit_Framework_MockObject_MockObject */
    private $decorator;

    public function setUp(): void
    {
        parent::setUp();

        $this->decorator            = $this->createMock(\Tracker_FormElement_Field_List_BindDecorator::class);
        $this->form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $this->decorator_retriever  = $this->createMock(BindDecoratorRetriever::class);
        $tracker                    = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(651);
        $this->artifact = $this->createMock(Tracker_Artifact::class);
        $this->artifact->method('getTracker')->willReturn($tracker);
        $this->current_user         = $this->createMock(PFUser::class);
        $this->selectbox            = $this->createMock(\Tracker_FormElement_Field_Selectbox::class);
        $this->accent_color_builder = new AccentColorBuilder($this->form_element_factory, $this->decorator_retriever);
    }

    public function testItBuildsATLPColor()
    {
        $this->form_element_factory->method('getSelectboxFieldByNameForUser')->willReturn($this->selectbox);
        $this->selectbox->method('userCanRead')->willReturn(true);
        $this->decorator_retriever->method('getDecoratorForFirstValue')->willReturn($this->decorator);
        $this->decorator->expects($this->once())->method('isUsingOldPalette')->willReturn(false);
        $this->decorator->tlp_color_name = 'neon-green';

        $color = $this->accent_color_builder->build($this->artifact, $this->current_user);

        $this->assertEquals('neon-green', $color->getColor());
        $this->assertFalse($color->isLegacyColor());
    }

    public function testItBuildsALegacyColor()
    {
        $this->form_element_factory->method('getSelectboxFieldByNameForUser')->willReturn($this->selectbox);
        $this->selectbox->method('userCanRead')->willReturn(true);
        $this->decorator_retriever->method('getDecoratorForFirstValue')->willReturn($this->decorator);
        $this->decorator->expects($this->once())->method('isUsingOldPalette')->willReturn(true);
        $rgb_string = 'rgb(192, 192, 0);';
        $this->decorator->expects($this->once())->method('css')->willReturn($rgb_string);

        $color = $this->accent_color_builder->build($this->artifact, $this->current_user);

        $this->assertEquals($rgb_string, $color->getColor());
        $this->assertTrue($color->isLegacyColor());
    }

    public function testItBuildsAnEmptyColorWhenNoDecorator()
    {
        $this->form_element_factory->method('getSelectboxFieldByNameForUser')->willReturn($this->selectbox);
        $this->selectbox->method('userCanRead')->willReturn(true);
        $this->decorator_retriever->expects($this->once())->method('getDecoratorForFirstValue')->willThrowException(
            new NoBindDecoratorException()
        );

        $color = $this->accent_color_builder->build($this->artifact, $this->current_user);

        $this->assertEquals('', $color->getColor());
        $this->assertTrue($color->isLegacyColor());
    }

    public function testItBuildsAnEmptyColorWhenNoChangesetValue()
    {
        $this->form_element_factory->method('getSelectboxFieldByNameForUser')->willReturn($this->selectbox);
        $this->selectbox->method('userCanRead')->willReturn(true);
        $this->decorator_retriever->expects($this->once())->method('getDecoratorForFirstValue')->willThrowException(
            new NoChangesetValueException()
        );

        $color = $this->accent_color_builder->build($this->artifact, $this->current_user);

        $this->assertEquals('', $color->getColor());
        $this->assertTrue($color->isLegacyColor());
    }

    public function testItBuildsAnEmptyColorWhenNoChangeset()
    {
        $this->form_element_factory->method('getSelectboxFieldByNameForUser')->willReturn($this->selectbox);
        $this->selectbox->method('userCanRead')->willReturn(true);
        $this->decorator_retriever->expects($this->once())->method('getDecoratorForFirstValue')->willThrowException(
            new NoChangesetException()
        );

        $color = $this->accent_color_builder->build($this->artifact, $this->current_user);

        $this->assertEquals('', $color->getColor());
        $this->assertTrue($color->isLegacyColor());
    }

    public function testItBuildsAnEmptyColorWhenCurrentUserCantReadTypeField()
    {
        $this->form_element_factory->method('getSelectboxFieldByNameForUser')->willReturn($this->selectbox);
        $this->selectbox->expects($this->once())->method('userCanRead')->willReturn(false);

        $color = $this->accent_color_builder->build($this->artifact, $this->current_user);

        $this->assertEquals('', $color->getColor());
        $this->assertTrue($color->isLegacyColor());
    }

    public function testItBuildsAnEmptyColorWhenNoTypeSelectbox()
    {
        $this->form_element_factory->expects($this->once())->method('getSelectboxFieldByNameForUser')->with(
            651,
            Tracker::TYPE_FIELD_NAME
        )->willReturn(null);

        $color = $this->accent_color_builder->build($this->artifact, $this->current_user);

        $this->assertEquals('', $color->getColor());
        $this->assertTrue($color->isLegacyColor());
    }
}
