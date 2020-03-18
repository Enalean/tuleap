<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

declare(strict_types = 1);

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;

final class Tracker_FormElement_Field_TextTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, \Tuleap\GlobalLanguageMock, \Tuleap\GlobalResponseMock;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|null
     */
    private $previous_value;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Value_TextDao
     */
    private $value_dao;
    /**
     * @var \Mockery\Mock | Tracker_FormElement_Field_Text
     */
    private $text_field;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $this->user = \Mockery::spy(\PFUser::class);

        $this->text_field = \Mockery::mock(\Tracker_FormElement_Field_Text::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $this->text_field->shouldReceive('getCurrentUser')->andReturn($this->user);

        $this->value_dao = \Mockery::spy(\Tracker_FormElement_Field_Value_TextDao::class);
        $this->text_field->shouldReceive('getValueDao')->andReturns($this->value_dao);

        $this->previous_value = \Mockery::spy(\Tracker_Artifact_ChangesetValue_Text::class)
            ->shouldReceive('getText')->andReturns('1')->getMock();
    }

    public function testNoDefaultValue(): void
    {
        $this->text_field->shouldReceive('getProperty')->with('default_value')
            ->andReturns(null)->once();

        $this->assertFalse($this->text_field->hasDefaultValue());
    }

    public function testDefaultValue(): void
    {
        $this->text_field->shouldReceive('getProperty')->with('default_value')
            ->andReturns('foo bar long text with nice stories');

        $this->assertTrue($this->text_field->hasDefaultValue());
        $this->assertEquals(
            [
                'content' => 'foo bar long text with nice stories',
                'format'  => 'text'
            ],
            $this->text_field->getDefaultValue()
        );
    }

    public function testGetChangesetValue(): void
    {
        $result = ['id' => 123, 'field_id' => 1, 'value' => 'My Text', 'body_format' => 'text'];
        $this->value_dao->shouldReceive('searchById')->andReturns(TestHelper::arrayToDar($result));

        $this->assertInstanceOf(
            Tracker_Artifact_ChangesetValue_Text::class,
            $this->text_field->getChangesetValue(\Mockery::spy(\Tracker_Artifact_Changeset::class), 123, false)
        );
    }

    public function testGetChangesetValueDoesntExist(): void
    {
        $result = [];
        $this->value_dao->shouldReceive('searchById')->andReturns(TestHelper::arrayToDar($result));

        $this->assertNull($this->text_field->getChangesetValue(null, 123, false));
    }

    public function testSpecialCharactersInCSVExport(): void
    {
        $whatever_report = \Mockery::spy(\Tracker_Report::class);

        $this->assertEquals(
            "Une chaine sans accent",
            $this->text_field->fetchCSVChangesetValue(null, null, "Une chaine sans accent", $whatever_report)
        );
        $this->assertEquals(
            "Lé chaîne avé lê àccent dô où ça",
            $this->text_field->fetchCSVChangesetValue(null, null, "Lé chaîne avé lê àccent dô où ça", $whatever_report)
        );
        $this->assertEquals(
            "This, or that",
            $this->text_field->fetchCSVChangesetValue(null, null, "This, or that", $whatever_report)
        );
        $this->assertEquals(
            "This; or that",
            $this->text_field->fetchCSVChangesetValue(null, null, "This; or that", $whatever_report)
        );
        $this->assertEquals(
            "This thing is > that thing",
            $this->text_field->fetchCSVChangesetValue(null, null, "This thing is > that thing", $whatever_report)
        );
        $this->assertEquals(
            "This thing & that thing",
            $this->text_field->fetchCSVChangesetValue(null, null, "This thing & that thing", $whatever_report)
        );
    }

    public function testIsValid(): void
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);

        $rule_string = \Mockery::spy(\Rule_String::class);
        $rule_string->shouldReceive('isValid')->andReturns(true);

        $this->text_field->shouldReceive('getRuleString')->andReturns($rule_string);

        $this->assertTrue($this->text_field->isValid($artifact, "Du texte"));
    }

    public function testHasChanges(): void
    {
        $value = \Mockery::spy(\Tracker_Artifact_ChangesetValue_Text::class);
        $value->shouldReceive('getText')->andReturns('v1');

        $this->assertTrue(
            $this->text_field->hasChanges(\Mockery::spy(\Tracker_Artifact::class), $value, ['content' => 'v2'])
        );
    }

    public function testIsValidRequiredField(): void
    {
        $this->text_field->shouldReceive('isRequired')->andReturns(true);

        $artifact = \Mockery::mock(\Tracker_Artifact::class);
        $this->assertTrue($this->text_field->isValid($artifact, 'This is a text'));
        $this->assertTrue($this->text_field->isValid($artifact, '2009-08-45'));
        $this->assertFalse($this->text_field->isValid($artifact, 25));
        $this->assertFalse($this->text_field->isValidRegardingRequiredProperty($artifact, ''));
        $this->assertFalse($this->text_field->isValidRegardingRequiredProperty($artifact, null));
    }

    public function testIsValidNotRequiredField(): void
    {
        $this->text_field->shouldReceive('isRequired')->andReturns(false);

        $value_1 = [
            'content' => 'This is a text',
            'format'  => 'text'
        ];

        $value_2 = [
            'content' => '2009-08-45',
            'format'  => 'text'
        ];

        $value_3 = [
            'content' => 25,
            'format'  => 'text'
        ];

        $value_4 = [
            'content' => '',
            'format'  => 'text'
        ];

        $value_5 = [
            'content' => null,
            'format'  => 'text'
        ];

        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $this->text_field->isValid($artifact, $value_1);
        $this->text_field->isValid($artifact, $value_2);
        $this->text_field->isValid($artifact, $value_3);
        $this->text_field->isValid($artifact, $value_4);
        $this->text_field->isValid($artifact, $value_5);
    }

    public function testGetFieldData(): void
    {
        $this->assertEquals('this is a text value', $this->text_field->getFieldData('this is a text value'));
    }

    public function testBuildMatchExpression(): void
    {
        $data_access = Mockery::mock(LegacyDataAccessInterface::class);
        $data_access->shouldReceive('quoteLikeValueSurround')->with(
            'tutu'
        )->andReturns("'%tutu%'")->getMock();
        $data_access->shouldReceive('quoteLikeValueSurround')->with('toto')->andReturns("'%toto%'");
        $data_access->shouldReceive('quoteSmart')->with('regexp')->andReturns("'regexp'");

        $dao = \Mockery::spy(\Tracker_Report_Criteria_Text_ValueDao::class)->shouldReceive('getDa')->andReturns(
            $data_access
        )->getMock();
        $this->text_field->shouldReceive('getCriteriaDao')->andReturns($dao);

        $this->assertEquals("field LIKE '%tutu%'", $this->text_field->buildMatchExpression('field', 'tutu'));
        $this->assertEquals(
            "field LIKE '%tutu%' AND field LIKE '%toto%'",
            $this->text_field->buildMatchExpression('field', 'tutu toto')
        );
        $this->assertEquals("field RLIKE 'regexp'", $this->text_field->buildMatchExpression('field', '/regexp/'));
        $this->assertEquals("field NOT RLIKE 'regexp'", $this->text_field->buildMatchExpression('field', '!/regexp/'));
    }

    /**
     * @see https://tuleap.net/plugins/tracker?aid=6435
     */
    public function testItIsEmptyWhenThereIsNoContent(): void
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $this->assertTrue(
            $this->text_field->isEmpty(
                [
                    'format'  => 'text',
                    'content' => ''
                ],
                $artifact
            )
        );
    }

    /**
     * @see https://tuleap.net/plugins/tracker?aid=6435
     */
    public function testItIsEmptyWhenThereIsOnlyWhitespaces(): void
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $this->assertTrue(
            $this->text_field->isEmpty(
                [
                    'format'  => 'text',
                    'content' => '   '
                ],
                $artifact
            )
        );
    }

    /**
     * @see https://tuleap.net/plugins/tracker?aid=6435
     */
    public function testItIsNotEmptyWhenThereIsContent(): void
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $this->assertFalse(
            $this->text_field->isEmpty(
                [
                    'format'  => 'text',
                    'content' => 'bla'
                ],
                $artifact
            )
        );
    }

    public function testItIsEmptyWhenValueIsAnEmptyString(): void
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $this->assertTrue($this->text_field->isEmpty('', $artifact));
    }

    public function testItIsNotEmptyWhenValueIsAStringWithContent(): void
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $this->assertFalse($this->text_field->isEmpty('aaa', $artifact));
    }

    public function testItReturnsTheValueIndexedByFieldName(): void
    {
        $value = [
            "field_id" => 873,
            "value"    => [
                'content' => 'My awesome content',
                'format'  => 'text',
            ]
        ];

        $fields_data = $this->text_field->getFieldDataFromRESTValueByField($value);

        $this->assertEquals('My awesome content', $fields_data['content']);
        $this->assertEquals('text', $fields_data['format']);
    }

    public function testItReturnsTrueIfThereIsAChange(): void
    {
        $new_value = [
            'content' => '1.0',
            'format'  => 'text'
        ];

        $this->assertTrue(
            $this->text_field->hasChanges(\Mockery::spy(\Tracker_Artifact::class), $this->previous_value, $new_value)
        );
    }

    public function testItReturnsFalseIfThereIsNoChange(): void
    {
        $new_value = [
            'content' => '1',
            'format'  => 'text'
        ];

        $this->assertFalse(
            $this->text_field->hasChanges(\Mockery::spy(\Tracker_Artifact::class), $this->previous_value, $new_value)
        );
    }

    public function testItReturnsFalseIfOnlyTheFormatChanged(): void
    {
        $new_value = [
            'content' => '1',
            'format'  => 'html'
        ];

        $this->assertFalse(
            $this->text_field->hasChanges(\Mockery::spy(\Tracker_Artifact::class), $this->previous_value, $new_value)
        );
    }
}
