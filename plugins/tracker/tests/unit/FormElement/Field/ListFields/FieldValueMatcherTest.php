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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_BindValue;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use User\XML\Import\IFindUserFromXMLReference;

#[DisableReturnValueGenerationForTestDoubles]
final class FieldValueMatcherTest extends TestCase
{
    private FieldValueMatcher $matcher;
    private ListField $source_field;
    private ListField $destination_field;
    private Tracker_FormElement_Field_List_Bind_Static&MockObject $source_field_bind;
    private Tracker_FormElement_Field_List_Bind_Static&MockObject $destination_field_bind;
    private ListField&MockObject $destination_user_field;
    private SimpleXMLElement $xml;
    private IFindUserFromXMLReference&MockObject $user_finder;

    #[\Override]
    public function setUp(): void
    {
        $this->source_field           = SelectboxFieldBuilder::aSelectboxField(154)->build();
        $this->destination_field      = SelectboxFieldBuilder::aSelectboxField(155)->build();
        $this->source_field_bind      = $this->createMock(Tracker_FormElement_Field_List_Bind_Static::class);
        $this->destination_field_bind = $this->createMock(Tracker_FormElement_Field_List_Bind_Static::class);

        $this->source_field->setBind($this->source_field_bind);
        $this->destination_field->setBind($this->destination_field_bind);

        $this->destination_user_field = $this->createMock(ListField::class);

        $this->xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <value format="ldap">101</value>
        ');

        $this->user_finder = $this->createMock(IFindUserFromXMLReference::class);
        $this->matcher     = new FieldValueMatcher($this->user_finder);
    }

    #[DataProvider('dataProviderMatchingValue')]
    public function testItMatchesValueByDuckTyping(
        Tracker_FormElement_Field_List_BindValue $source_value,
        array $values,
        ?int $expected_bind_value_id,
    ): void {
        $this->source_field_bind->method('getValue')->with(101)->willReturn($source_value);
        $this->destination_field_bind->method('getAllValues')->willReturn($values);

        $matching_value = $this->matcher->getMatchingValueByDuckTyping($this->source_field, $this->destination_field, 101);

        self::assertEquals($expected_bind_value_id, $matching_value);
    }

    public function testItReturnsNoneValueIfSourceValueIsAlsoNoneAndTargetValueNotRequired(): void
    {
        $matching_value = $this->matcher->getMatchingValueByDuckTyping($this->source_field, $this->destination_field, 100);

        self::assertEquals(100, $matching_value);
    }

    public function testItReturnsNoneValueIfSourceValueIsNotProvided(): void
    {
        $matching_value = $this->matcher->getMatchingValueByDuckTyping($this->source_field, $this->destination_field, 0);

        self::assertEquals(100, $matching_value);
    }

    public static function dataProviderMatchingValue(): array
    {
        return [
            'It retrieves matching value by label'                          => [
                ListStaticValueBuilder::aStaticValue('2')->withId(101)->build(),
                [
                    ListStaticValueBuilder::aStaticValue('1')->withId(200)->build(),
                    ListStaticValueBuilder::aStaticValue('2')->withId(201)->build(),
                ],
                201,
            ],
            'It matches value label with different cases'                   => [
                ListStaticValueBuilder::aStaticValue('a')->withId(101)->build(),
                [
                    ListStaticValueBuilder::aStaticValue('A')->withId(200)->build(),
                    ListStaticValueBuilder::aStaticValue('b')->withId(201)->build(),
                ],
                200,
            ],
            'It matches value even if it is hidden'                         => [
                ListStaticValueBuilder::aStaticValue('2')->withId(101)->build(),
                [
                    ListStaticValueBuilder::aStaticValue('1')->withId(200)->build(),
                    ListStaticValueBuilder::aStaticValue('2')->withId(201)->build(),
                ],
                201,
            ],
            'It matches first value if multiple values have the same label' => [
                ListStaticValueBuilder::aStaticValue('1')->withId(101)->build(),
                [
                    ListStaticValueBuilder::aStaticValue('1')->withId(200)->build(),
                    ListStaticValueBuilder::aStaticValue('1')->withId(201)->build(),
                ],
                200,
            ],
            'It returns null if no matching value'                          => [
                ListStaticValueBuilder::aStaticValue('3')->withId(101)->build(),
                [
                    ListStaticValueBuilder::aStaticValue('1')->withId(200)->build(),
                    ListStaticValueBuilder::aStaticValue('2')->withId(201)->build(),
                    ListStaticValueBuilder::aStaticValue('0')->withId(202)->build(),
                ],
                null,
            ],
        ];
    }

    #[DataProvider('dataProviderMatchingValue')]
    public function testItMatchesBindValueByDuckTyping(
        Tracker_FormElement_Field_List_BindValue $source_value,
        array $values,
        ?int $expected_bind_value_id,
    ): void {
        $this->destination_field_bind->method('getAllValues')->willReturn($values);

        $matching_value = $this->matcher->getMatchingBindValueByDuckTyping($source_value, $this->destination_field);
        self::assertEquals(
            $expected_bind_value_id,
            ($matching_value !== null) ? $matching_value->getId() : $matching_value
        );
    }

    public function testItReturnsTrueIfThereIsAMatchingUserValue(): void
    {
        $this->user_finder->method('getUser')->willReturn(UserTestBuilder::anActiveUser()->withId(101)->build());
        $this->destination_user_field->method('checkValueExists')->with(101)->willReturn(true);

        self::assertTrue(
            $this->matcher->isSourceUserValueMatchingADestinationUserValue($this->destination_user_field, $this->xml)
        );
    }

    public function testItReturnsFalseIfUserIsAnonymous(): void
    {
        $this->user_finder->method('getUser')->willReturn(UserTestBuilder::anAnonymousUser()->build());

        self::assertFalse(
            $this->matcher->isSourceUserValueMatchingADestinationUserValue($this->destination_user_field, $this->xml)
        );
    }

    public function testItReturnsFalseIfThereIsNoMatchingUserValue(): void
    {
        $this->user_finder->method('getUser')->willReturn(UserTestBuilder::anActiveUser()->withId(101)->build());
        $this->destination_user_field->method('checkValueExists')->with(101)->willReturn(false);

        self::assertFalse(
            $this->matcher->isSourceUserValueMatchingADestinationUserValue($this->destination_user_field, $this->xml)
        );
    }
}
