<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ListFields;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use SimpleXMLElement;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Static;
use XMLImportHelper;

final class FieldValueMatcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var FieldValueMatcher
     */
    private $matcher;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElement_Field_List
     */
    private $source_field;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElement_Field_List
     */
    private $target_field;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElement_Field_List_Bind_Static
     */
    private $source_field_bind;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElement_Field_List_Bind_Static
     */
    private $target_field_bind;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElement_Field_List
     */
    private $target_user_field;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var SimpleXMLElement
     */
    private $xml;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|XMLImportHelper
     */
    private $user_finder;

    public function setUp(): void
    {
        $this->source_field      = Mockery::mock(Tracker_FormElement_Field_List::class);
        $this->target_field      = Mockery::mock(Tracker_FormElement_Field_List::class);
        $this->source_field_bind = Mockery::mock(Tracker_FormElement_Field_List_Bind_Static::class);
        $this->target_field_bind = Mockery::mock(Tracker_FormElement_Field_List_Bind_Static::class);

        $this->source_field->shouldReceive('getBind')->andReturn($this->source_field_bind);
        $this->target_field->shouldReceive('getBind')->andReturn($this->target_field_bind);

        $this->target_user_field = Mockery::mock(Tracker_FormElement_Field_List::class);

        $this->user = Mockery::mock(PFUser::class);
        $this->user->shouldReceive('getId')->andReturn(101);

        $this->xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <value format="ldap">101</value>
        ');

        $this->user_finder = Mockery::mock(XMLImportHelper::class);
        $this->matcher     = new FieldValueMatcher($this->user_finder);
    }

    /**
     * @dataProvider dataProviderMatchingValue
     */
    public function testItMatchesValueByDuckTyping(
        \Tracker_FormElement_Field_List_BindValue $source_value,
        array $values,
        ?int $expected_bind_value_id,
    ): void {
        $this->source_field_bind->shouldReceive('getValue')->with(101)->andReturn($source_value);
        $this->target_field_bind->shouldReceive('getAllValues')->andReturn($values);

        $matching_value = $this->matcher->getMatchingValueByDuckTyping($this->source_field, $this->target_field, 101);

        self::assertEquals($expected_bind_value_id, $matching_value);
    }

    public function testItReturnsNoneValueIfSourceValueIsAlsoNoneAndTargetValueNotRequired(): void
    {
        $matching_value = $this->matcher->getMatchingValueByDuckTyping($this->source_field, $this->target_field, 100);

        $this->assertEquals(100, $matching_value);
    }

    public function testItReturnsNoneValueIfSourceValueIsNotProvided(): void
    {
        $matching_value = $this->matcher->getMatchingValueByDuckTyping($this->source_field, $this->target_field, 0);

        $this->assertEquals(100, $matching_value);
    }

    public static function dataProviderMatchingValue(): array
    {
        return [
            'It retrieves matching value by label'                          => [
                new \Tracker_FormElement_Field_List_Bind_StaticValue(101, '2', 'Irrelevant', 0, 0),
                [
                    new \Tracker_FormElement_Field_List_Bind_StaticValue(200, '1', 'Irrelevant', 0, 0),
                    new \Tracker_FormElement_Field_List_Bind_StaticValue(201, '2', 'Irrelevant', 1, 0),
                ],
                201,
            ],
            'It matches value label with different cases'                   => [
                new \Tracker_FormElement_Field_List_Bind_StaticValue(101, 'a', 'Irrelevant', 0, 0),
                [
                    new \Tracker_FormElement_Field_List_Bind_StaticValue(200, 'A', 'Irrelevant', 0, 0),
                    new \Tracker_FormElement_Field_List_Bind_StaticValue(201, 'b', 'Irrelevant', 1, 0),
                ],
                200,
            ],
            'It matches value even if it is hidden'                         => [
                new \Tracker_FormElement_Field_List_Bind_StaticValue(101, '2', 'Irrelevant', 0, 0),
                [
                    new \Tracker_FormElement_Field_List_Bind_StaticValue(200, '1', 'Irrelevant', 0, 0),
                    new \Tracker_FormElement_Field_List_Bind_StaticValue(201, '2', 'Irrelevant', 1, 1),
                ],
                201,
            ],
            'It matches first value if multiple values have the same label' => [
                new \Tracker_FormElement_Field_List_Bind_StaticValue(101, '1', 'Irrelevant', 0, 0),
                [
                    new \Tracker_FormElement_Field_List_Bind_StaticValue(200, '1', 'Irrelevant', 0, 0),
                    new \Tracker_FormElement_Field_List_Bind_StaticValue(201, '1', 'Irrelevant', 1, 0),
                ],
                200,
            ],
            'It returns null if no matching value'                          => [
                new \Tracker_FormElement_Field_List_Bind_StaticValue(101, '3', 'Irrelevant', 0, 0),
                [
                    new \Tracker_FormElement_Field_List_Bind_StaticValue(200, '1', 'Irrelevant', 0, 0),
                    new \Tracker_FormElement_Field_List_Bind_StaticValue(201, '2', 'Irrelevant', 1, 0),
                    new \Tracker_FormElement_Field_List_Bind_StaticValue(201, '0', 'Irrelevant', 1, 0),
                ],
                null,
            ],
        ];
    }

    /**
     * @dataProvider dataProviderMatchingValue
     */
    public function testItMatchesBindValueByDuckTyping(
        \Tracker_FormElement_Field_List_BindValue $source_value,
        array $values,
        ?int $expected_bind_value_id,
    ): void {
        $this->target_field_bind->shouldReceive('getAllValues')->andReturn($values);

        $matching_value = $this->matcher->getMatchingBindValueByDuckTyping($source_value, $this->target_field);
        self::assertEquals(
            $expected_bind_value_id,
            ($matching_value !== null) ? $matching_value->getId() : $matching_value
        );
    }

    public function testItReturnsTrueIfThereIsAMatchingUserValue(): void
    {
        $this->user->shouldReceive('isAnonymous')->andReturn(false);
        $this->user_finder->shouldReceive('getUser')->andReturn($this->user);
        $this->target_user_field->shouldReceive('checkValueExists')->with(101)->andReturn(true);

        $this->assertTrue(
            $this->matcher->isSourceUserValueMatchingATargetUserValue($this->target_user_field, $this->xml)
        );
    }

    public function testItReturnsFalseIfUserIsAnonymous(): void
    {
        $this->user->shouldReceive('isAnonymous')->andReturn(true);
        $this->user_finder->shouldReceive('getUser')->andReturn($this->user);

        $this->assertFalse(
            $this->matcher->isSourceUserValueMatchingATargetUserValue($this->target_user_field, $this->xml)
        );
    }

    public function testItReturnsFalseIfThereIsNoMatchingUserValue(): void
    {
        $this->user->shouldReceive('isAnonymous')->andReturn(false);
        $this->user_finder->shouldReceive('getUser')->andReturn($this->user);
        $this->target_user_field->shouldReceive('checkValueExists')->with(101)->andReturn(false);

        $this->assertFalse(
            $this->matcher->isSourceUserValueMatchingATargetUserValue($this->target_user_field, $this->xml)
        );
    }
}
