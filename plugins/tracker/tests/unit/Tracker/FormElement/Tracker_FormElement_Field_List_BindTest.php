<?php
/**
 * Copyright (c) Enalean, 2013 - present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

final class Tracker_FormElement_Field_List_BindTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_List_BindValue
     */
    private $v2;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_List_BindValue
     */
    private $v1;

    /**
     * @var \Mockery\Mock|Tracker_FormElement_Field_List_Bind
     */
    private $bind;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field
     */
    private $field;

    protected function setUp(): void
    {
        $decorator   = Mockery::mock(Tracker_FormElement_Field_List_BindDecorator::class);
        $this->field = Mockery::mock(Tracker_FormElement_Field::class);
        $this->bind  = Mockery::mock(Tracker_FormElement_Field_List_Bind::class, [$this->field, [], $decorator])
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $this->v1 = Mockery::mock(Tracker_FormElement_Field_List_BindValue::class);
        $this->v2 = Mockery::mock(Tracker_FormElement_Field_List_BindValue::class);
    }

    public function testItDelegatesFormattingToValues(): void
    {
        $this->v1->shouldReceive('fetchFormattedForJson')->once();
        $this->v2->shouldReceive('fetchFormattedForJson')->once();

        $this->bind->shouldReceive('getAllValues')->andReturn([$this->v1, $this->v2]);

        $this->bind->fetchFormattedForJson();
    }

    public function testItFormatsValuesForJson(): void
    {
        $this->v1->shouldReceive('fetchFormattedForJson')->andReturn('whatever 1');
        $this->v2->shouldReceive('fetchFormattedForJson')->andReturn('whatever 2');
        $this->bind->shouldReceive('getAllValues')->andReturn([$this->v1, $this->v2]);

        $this->assertSame(
            [
                'whatever 1',
                'whatever 2',
            ],
            $this->bind->fetchFormattedForJson()
        );
    }

    public function testItSendsAnEmptyArrayInJSONFormatWhenNoValues(): void
    {
        $this->bind->shouldReceive('getAllValues')->andReturn([]);
        $this->assertSame(
            [],
            $this->bind->fetchFormattedForJson()
        );
    }

    public function testItVerifiesAValueExist(): void
    {
        $this->bind->shouldReceive('getAllValues')->andReturn([101 => 101]);

        $this->assertTrue($this->bind->isExistingValue(101));
        $this->assertFalse($this->bind->isExistingValue(201));
    }

    public function testItFilterDefaultValuesReturnEmptyArrayIfNoDefaultValues(): void
    {
        $default_value_dao = Mockery::mock(Tracker_FormElement_Field_List_Bind_DefaultvalueDao::class);

        $this->bind->shouldReceive("filterDefaultValues")->never();
        $this->field->shouldReceive("getId")->andReturn(42);

        $this->bind->shouldReceive("getDefaultValueDao")->andReturn($default_value_dao);
        $default_value_dao->shouldReceive("save")->withArgs([42, []])->andReturn(true);

        $params = [];
        $this->bind->process($params, true);
    }

    public function testItExtractDefaultValues(): void
    {
        $default_value_dao = Mockery::mock(Tracker_FormElement_Field_List_Bind_DefaultvalueDao::class);
        $this->field->shouldReceive("getId")->andReturn(42);
        $this->bind->shouldReceive("getDefaultValueDao")->andReturn($default_value_dao);

        $default_value_dao
            ->shouldReceive("save")
            ->withArgs([42, ["111", "112"]])
            ->andReturn(true);

        $params = ["default" => ["111", "112"]];
        $this->bind->process($params, true);
    }

    public function testItExtractDefaultValuesFromOpenValue(): void
    {
        $field     = Mockery::mock(Tracker_FormElement_Field_OpenList::class);
        $decorator = Mockery::mock(Tracker_FormElement_Field_List_BindDecorator::class);

        $bind              = Mockery::mock(Tracker_FormElement_Field_List_Bind_Users::class, [$field, [], [], $decorator])
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $default_value_dao = Mockery::mock(Tracker_FormElement_Field_List_Bind_DefaultvalueDao::class);
        $field->shouldReceive("getId")->andReturn(42);

        $default_value_dao->shouldReceive("save")->andReturn(true);
        $default_value_dao
            ->shouldReceive("save")
            ->withArgs([42, ["103", "111", "b117"]])
            ->andReturn(true);

        $bind->shouldReceive("getDefaultValueDao")->andReturn($default_value_dao);

        $params = ["default" => ["103,111,b117"]];
        $bind->process($params, true);
    }
}
