<?php
/**
 * Copyright (c) Enalean 2021-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

namespace Tuleap\GraphOnTrackersV5\DataTransformation;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElementFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\GraphOnTrackersV5\GraphicLibrary\GraphOnTrackersV5_Engine_Bar;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\User\CurrentUserWithLoggedInInformation;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GraphOnTrackersV5ChartBarDataBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    private GraphOnTrackersV5_Chart_BarDataBuilder&MockObject $builder;
    private Tracker_FormElementFactory&MockObject $factory;
    private PFUser $user;

    #[\Override]
    protected function setUp(): void
    {
        $this->user = UserTestBuilder::buildWithId(101);
        UserManager::instance()->setCurrentUser(CurrentUserWithLoggedInInformation::fromLoggedInUser($this->user));
        $this->builder = $this->createPartialMock(
            GraphOnTrackersV5_Chart_BarDataBuilder::class,
            ['buildParentProperties', 'buildSourceField', 'getFieldGroupId', 'getFieldBaseId', 'getArtifactIds', 'getArtifactsLastChangesetIds', 'getQueryResult', 'getFormElementFactory']
        );
        $this->factory = $this->createMock(Tracker_FormElementFactory::class);
    }

    public function testItBuildsAnEngineForBarChart(): void
    {
        $engine = new GraphOnTrackersV5_Engine_Bar();
        $this->builder->expects($this->once())->method('buildParentProperties');
        $this->builder->expects($this->once())->method('buildSourceField')->willReturn($this->buildSourceField());

        $this->builder->expects($this->once())->method('getFieldGroupId')->willReturn(10);
        $this->builder->expects($this->once())->method('getFieldBaseId')->willReturn(10);

        $this->builder->expects($this->atLeast(2))->method('getArtifactIds')->willReturn('1,2,3');
        $this->builder->expects($this->once())->method('getArtifactsLastChangesetIds')->willReturn('100,200,300');

        $this->builder->expects($this->once())->method('getQueryResult')->willReturn([
            ['nb' => 10, 'user_defined__10_100' => 130, 'red' => null, 'green' => null, 'blue' => null, 'tlp_color_name' => null],
            ['nb' => 5, 'user_defined__10_100' => 131, 'red' => null, 'green' => null, 'blue' => null, 'tlp_color_name' => null],
            ['nb' => 3, 'user_defined__10_100' => 130, 'red' => null, 'green' => null, 'blue' => null, 'tlp_color_name' => null],
        ]);

        $this->builder->buildProperties($engine);

        $expected_data = [10, 5, 3];
        self::assertEquals($expected_data, $engine->data);
    }

    public function testItBuildAnEngineForGroupedBarChart(): void
    {
        $engine = new GraphOnTrackersV5_Engine_Bar();
        $this->builder->expects($this->once())->method('buildParentProperties');
        $source_field = $this->buildSourceField();

        $this->builder->expects($this->once())->method('buildSourceField')->willReturn($source_field);
        $this->builder->expects($this->once())->method('getFormElementFactory')->willReturn($this->factory);

        $group_field = $this->buildGroupField();
        $this->factory->expects($this->once())->method('getFormElementById')->willReturn($group_field);

        $this->builder->expects($this->atLeast(2))->method('getFieldGroupId')->willReturn(10);
        $this->builder->expects($this->once())->method('getFieldBaseId')->willReturn(20);
        $this->builder->expects($this->atLeast(2))->method('getArtifactIds')->willReturn('1,2,3');
        $this->builder->expects($this->once())->method('getArtifactsLastChangesetIds')->willReturn('100,200,300');

        $this->builder->expects($this->once())->method('getQueryResult')->willReturn([
            ['nb' => 10, 'user_defined__10_100' => 130, 'user_defined__10_98' => null, 'red' => null, 'green' => null, 'blue' => null, 'tlp_color_name' => null],
            ['nb' => 5, 'user_defined__10_100' => 131, 'user_defined__10_98' => 431, 'red' => null, 'green' => null, 'blue' => null, 'tlp_color_name' => null],
            ['nb' => 3, 'user_defined__10_100' => 130, 'user_defined__10_98' => 432, 'red' => null, 'green' => null, 'blue' => null, 'tlp_color_name' => null],
        ]);

        $this->builder->buildProperties($engine);

        $expected_data  = [130 => ['' => 10, 432 => 3], 131 => [431 => 5]];
        $expected_xaxis = [431 => 'Abc', 432 => 'Def', '' => null];
        self::assertEquals($expected_data, $engine->data);
        self::assertEquals($expected_xaxis, $engine->xaxis);
    }

    private function buildGroupField(): ListField
    {
        return ListStaticBindBuilder::aStaticBind(
            SelectboxFieldBuilder::aSelectboxField(98)
                ->withName('group_field')
                ->withReadPermission($this->user, true)
                ->build()
        )->withStaticValues([
            431 => 'Abc',
            432 => 'Def',
        ])->build()->getField();
    }

    private function buildSourceField(): ListField
    {
        return ListStaticBindBuilder::aStaticBind(
            SelectboxFieldBuilder::aSelectboxField(100)
                ->withName('source_field')
                ->withReadPermission($this->user, true)
                ->build()
        )->withStaticValues([
            130 => '123',
            131 => '456',
        ])->build()->getField();
    }
}
