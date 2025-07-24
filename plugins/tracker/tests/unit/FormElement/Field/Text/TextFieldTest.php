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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\Text;

use ParagonIE\EasyDB\EasyDB;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use TestHelper;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_Report_Criteria_Text_ValueDao;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;
use Tuleap\GlobalResponseMock;
use Tuleap\Option\Option;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueTextTestBuilder;
use Tuleap\Tracker\Test\Builders\ReportTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class TextFieldTest extends TestCase
{
    use GlobalResponseMock;

    private Tracker_Artifact_ChangesetValue_Text $previous_value;
    private TextValueDao&MockObject $value_dao;
    private TextField&MockObject $text_field;
    private PFUser $user;

    #[\Override]
    protected function setUp(): void
    {
        $this->user = UserTestBuilder::buildWithDefaults();

        $this->text_field = $this->createPartialMock(TextField::class, [
            'getCurrentUser', 'getDb', 'getValueDao', 'getProperty', 'isRequired', 'getCriteriaDao',
        ]);
        $this->text_field->method('getCurrentUser')->willReturn($this->user);

        $db = $this->createMock(EasyDB::class);
        $db->method('escapeLikeValue')->willReturnArgument(0);
        $this->text_field->method('getDb')->willReturn($db);

        $this->value_dao = $this->createMock(TextValueDao::class);
        $this->text_field->method('getValueDao')->willReturn($this->value_dao);

        $this->previous_value = ChangesetValueTextTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(1)->build(), $this->text_field)
            ->withValue('1', Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT)->build();
    }

    public function testNoDefaultValue(): void
    {
        $this->text_field->expects($this->once())->method('getProperty')->with('default_value')->willReturn(null);

        self::assertFalse($this->text_field->hasDefaultValue());
    }

    public function testDefaultValue(): void
    {
        $this->text_field->method('getProperty')->with('default_value')
            ->willReturn('foo bar long text with nice stories');

        self::assertTrue($this->text_field->hasDefaultValue());
        self::assertEquals(
            [
                'content' => 'foo bar long text with nice stories',
                'format'  => Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
            ],
            $this->text_field->getDefaultValue()
        );
    }

    public function testDefaultFormatIsCommonMark(): void
    {
        $this->text_field->method('getProperty')->with('default_value')->willReturn('any');
        $this->user->setPreference(PFUser::EDITION_DEFAULT_FORMAT, '');

        self::assertTrue($this->text_field->hasDefaultValue());
        self::assertEquals(
            [
                'content' => 'any',
                'format'  => Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
            ],
            $this->text_field->getDefaultValue()
        );
    }

    public function testTheFormatIsCommonMarkWhenTheUserHasCommonMarkFormatPreference(): void
    {
        $this->text_field->method('getProperty')->with('default_value')->willReturn('any');
        $this->user->setPreference(PFUser::EDITION_DEFAULT_FORMAT, Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT);

        self::assertTrue($this->text_field->hasDefaultValue());
        self::assertEquals(
            [
                'content' => 'any',
                'format'  => Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
            ],
            $this->text_field->getDefaultValue()
        );
    }

    public function testTheFormatIsHTMLAndContentConvertedToHTMLWhenTheUserHasHTMLFormatPreference(): void
    {
        $this->text_field->method('getProperty')->with('default_value')->willReturn("Eeny, meeny,\nminy, <b>moe");
        $this->user->setPreference(PFUser::EDITION_DEFAULT_FORMAT, Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);

        self::assertTrue($this->text_field->hasDefaultValue());
        self::assertEquals(
            [
                'content' => "<p>Eeny, meeny,<br />\nminy, &lt;b&gt;moe</p>",
                'format'  => Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT,
            ],
            $this->text_field->getDefaultValue()
        );
    }

    public function testTheFormatIsTextWhenTheUserHasTextFormatPreference(): void
    {
        $this->text_field->method('getProperty')->with('default_value')->willReturn('any');
        $this->user->setPreference(PFUser::EDITION_DEFAULT_FORMAT, Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT);

        self::assertTrue($this->text_field->hasDefaultValue());
        self::assertEquals(
            [
                'content' => 'any',
                'format'  => Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
            ],
            $this->text_field->getDefaultValue()
        );
    }

    public function testGetChangesetValue(): void
    {
        $result = ['id' => 123, 'field_id' => 1, 'value' => 'My Text', 'body_format' => 'text'];
        $this->value_dao->method('searchById')->willReturn(TestHelper::arrayToDar($result));

        self::assertInstanceOf(
            Tracker_Artifact_ChangesetValue_Text::class,
            $this->text_field->getChangesetValue(ChangesetTestBuilder::aChangeset(1)->build(), 123, false)
        );
    }

    public function testGetChangesetValueDoesntExist(): void
    {
        $result = [];
        $this->value_dao->method('searchById')->willReturn(TestHelper::arrayToDar($result));

        self::assertNull($this->text_field->getChangesetValue(ChangesetTestBuilder::aChangeset(1)->build(), 123, false));
    }

    public function testSpecialCharactersInCSVExport(): void
    {
        $whatever_report = ReportTestBuilder::aPublicReport()->build();
        $artifact_id     = 1;
        $changeset_id    = 100;

        self::assertEquals(
            'Une chaine sans accent',
            $this->text_field->fetchCSVChangesetValue($artifact_id, $changeset_id, 'Une chaine sans accent', $whatever_report)
        );
        self::assertEquals(
            'Lé chaîne avé lê àccent dô où ça',
            $this->text_field->fetchCSVChangesetValue($artifact_id, $changeset_id, 'Lé chaîne avé lê àccent dô où ça', $whatever_report)
        );
        self::assertEquals(
            'This, or that',
            $this->text_field->fetchCSVChangesetValue($artifact_id, $changeset_id, 'This, or that', $whatever_report)
        );
        self::assertEquals(
            'This; or that',
            $this->text_field->fetchCSVChangesetValue($artifact_id, $changeset_id, 'This; or that', $whatever_report)
        );
        self::assertEquals(
            'This thing is > that thing',
            $this->text_field->fetchCSVChangesetValue($artifact_id, $changeset_id, 'This thing is > that thing', $whatever_report)
        );
        self::assertEquals(
            'This thing & that thing',
            $this->text_field->fetchCSVChangesetValue($artifact_id, $changeset_id, 'This thing & that thing', $whatever_report)
        );
    }

    public function testHasChanges(): void
    {
        $value = ChangesetValueTextTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(1)->build(), $this->text_field)
            ->withValue('v1', Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT)
            ->build();

        self::assertTrue(
            $this->text_field->hasChanges(ArtifactTestBuilder::anArtifact(987)->build(), $value, ['content' => 'v2'])
        );
    }

    public function testHasChangesWithoutFormat(): void
    {
        $value = ChangesetValueTextTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(1)->build(), $this->text_field)
            ->withValue('v1', Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT)
            ->build();

        self::assertTrue(
            $this->text_field->hasChanges(ArtifactTestBuilder::anArtifact(987)->build(), $value, 'v2')
        );
    }

    public function testIsValidRequiredField(): void
    {
        $this->text_field->method('isRequired')->willReturn(true);

        $artifact = ArtifactTestBuilder::anArtifact(987)->build();
        self::assertTrue($this->text_field->isValid($artifact, 'This is a text'));
        self::assertTrue($this->text_field->isValid($artifact, '2009-08-45'));
        self::assertFalse($this->text_field->isValid($artifact, 25));
    }

    public function testIsValidNotRequiredField(): void
    {
        $this->text_field->method('isRequired')->willReturn(false);

        $value_1 = [
            'content' => 'This is a text',
            'format'  => 'text',
        ];

        $value_2 = [
            'content' => '2009-08-45',
            'format'  => 'text',
        ];

        $value_3 = [
            'content' => 25,
            'format'  => 'text',
        ];

        $value_4 = [
            'content' => '',
            'format'  => 'text',
        ];

        $value_5 = [
            'content' => null,
            'format'  => 'text',
        ];

        $artifact = ArtifactTestBuilder::anArtifact(987)->build();
        self::assertTrue($this->text_field->isValid($artifact, $value_1));
        self::assertTrue($this->text_field->isValid($artifact, $value_2));
        self::assertFalse($this->text_field->isValid($artifact, $value_3));
        self::assertTrue($this->text_field->isValid($artifact, $value_4));
        self::assertFalse($this->text_field->isValid($artifact, $value_5));
    }

    public function testGetFieldData(): void
    {
        self::assertEquals('this is a text value', $this->text_field->getFieldData('this is a text value'));
    }

    public function testBuildMatchExpression(): void
    {
        $data_access = $this->createMock(LegacyDataAccessInterface::class);
        $data_access->method('quoteLikeValueSurround')->willReturnCallback(static fn(string $str) => "'%$str%'");
        $data_access->method('quoteSmart')->with('regexp')->willReturn("'regexp'");

        $dao = $this->createMock(Tracker_Report_Criteria_Text_ValueDao::class);
        $dao->method('getDa')->willReturn($data_access);
        $this->text_field->method('getCriteriaDao')->willReturn($dao);
        $reflection = new ReflectionClass($this->text_field::class);
        $method     = $reflection->getMethod('buildMatchExpression');
        $method->setAccessible(true);

        self::assertFragment(
            'field LIKE ?',
            ['%tutu%'],
            $method->invoke($this->text_field, 'field', 'tutu'),
        );
        self::assertFragment(
            'field LIKE ? AND field LIKE ?',
            ['%tutu%', '%toto%'],
            $method->invoke($this->text_field, 'field', 'tutu toto'),
        );
        self::assertFragment(
            'field RLIKE ?',
            ['regexp'],
            $method->invoke($this->text_field, 'field', '/regexp/'),
        );
        self::assertFragment(
            'field NOT RLIKE ?',
            ['regexp'],
            $method->invoke($this->text_field, 'field', '!/regexp/'),
        );
    }

    /**
     * @param Option<ParametrizedSQLFragment> $fragment
     */
    private static function assertFragment(string $expected_sql, array $expected_parameters, Option $fragment): void
    {
        $fragment = $fragment->unwrapOr(null);
        if ($fragment === null) {
            self::fail('Does not match expected ' . $expected_sql);
        }

        self::assertEquals($expected_sql, $fragment->sql);
        self::assertEquals($expected_parameters, $fragment->parameters);
    }

    public function testIsValidRegardingRequiredPropertyWhichIsTrue(): void
    {
        $artifact       = ArtifactTestBuilder::anArtifact(987)->build();
        $submited_value = ['format' => 'html', 'content' => 'is content'];

        $this->text_field->method('isRequired')->willReturn(true);
        self::assertTrue($this->text_field->isValidRegardingRequiredProperty($artifact, $submited_value));
    }

    public function testItIsNotValidRegardingRequiredPropertyWhichIsTrue(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(987)->build();

        $this->text_field->method('isRequired')->willReturn(true);
        self::assertFalse($this->text_field->isValidRegardingRequiredProperty($artifact, ['format' => 'html', 'content' => '']));
        self::assertFalse($this->text_field->isValidRegardingRequiredProperty($artifact, ['format' => 'html', 'content' => null]));
        self::assertFalse($this->text_field->isValidRegardingRequiredProperty($artifact, ['format' => 'html']));
        self::assertFalse($this->text_field->isValidRegardingRequiredProperty($artifact, null));
    }

    public function testItIsValidRegardingRequiredPropertyWhichIsFalse(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(987)->build();

        $this->text_field->method('isRequired')->willReturn(false);
        self::assertTrue($this->text_field->isValidRegardingRequiredProperty($artifact, ['format' => 'html', 'content' => 'the content']));
        self::assertTrue($this->text_field->isValidRegardingRequiredProperty($artifact, ['format' => 'html', 'content' => '']));
        self::assertTrue($this->text_field->isValidRegardingRequiredProperty($artifact, ['format' => 'html', 'content' => null]));
        self::assertTrue($this->text_field->isValidRegardingRequiredProperty($artifact, null));
    }

    public function testIsValidRegardingRequiredPropertyWhichIsFalseAndNoContent(): void
    {
        $artifact       = ArtifactTestBuilder::anArtifact(987)->build();
        $submited_value = ['format' => 'html', 'content' => ''];

        $this->text_field->method('isRequired')->willReturn(false);
        self::assertTrue($this->text_field->isValidRegardingRequiredProperty($artifact, $submited_value));
    }

    public function testIsValidRegardingRequiredPropertyInCSVContext(): void
    {
        $artifact       = ArtifactTestBuilder::anArtifact(987)->build();
        $submited_value = 'my content';

        $this->text_field->method('isRequired')->willReturn(true);
        self::assertTrue($this->text_field->isValidRegardingRequiredProperty($artifact, $submited_value));
    }

    public function testIsNotValidRegardingRequiredPropertyInCSVContext(): void
    {
        $artifact       = ArtifactTestBuilder::anArtifact(987)->build();
        $submited_value = '';

        $this->text_field->method('isRequired')->willReturn(true);
        self::assertFalse($this->text_field->isValidRegardingRequiredProperty($artifact, $submited_value));
    }

    /**
     * @see https://tuleap.net/plugins/tracker?aid=6435
     */
    public function testItIsEmptyWhenThereIsNoContent(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(987)->build();
        self::assertTrue(
            $this->text_field->isEmpty(
                [
                    'format'  => 'text',
                    'content' => '',
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
        $artifact = ArtifactTestBuilder::anArtifact(987)->build();
        self::assertTrue(
            $this->text_field->isEmpty(
                [
                    'format'  => 'text',
                    'content' => '   ',
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
        $artifact = ArtifactTestBuilder::anArtifact(987)->build();
        self::assertFalse(
            $this->text_field->isEmpty(
                [
                    'format'  => 'text',
                    'content' => 'bla',
                ],
                $artifact
            )
        );
    }

    public function testItIsEmptyWhenValueIsAnEmptyString(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(987)->build();
        self::assertTrue($this->text_field->isEmpty('', $artifact));
    }

    public function testItIsNotEmptyWhenValueIsAStringWithContent(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(987)->build();
        self::assertFalse($this->text_field->isEmpty('aaa', $artifact));
    }

    public function testItReturnsTheValueIndexedByFieldName(): void
    {
        $value = [
            'field_id' => 873,
            'value'    => [
                'content' => 'My awesome content',
                'format'  => 'text',
            ],
        ];

        $fields_data = $this->text_field->getFieldDataFromRESTValueByField($value);

        self::assertEquals('My awesome content', $fields_data['content']);
        self::assertEquals('text', $fields_data['format']);
    }

    public function testItReturnsTrueIfThereIsAChange(): void
    {
        $new_value = [
            'content' => '1.0',
            'format'  => 'text',
        ];

        self::assertTrue(
            $this->text_field->hasChanges(ArtifactTestBuilder::anArtifact(987)->build(), $this->previous_value, $new_value)
        );
    }

    public function testItReturnsFalseIfThereIsNoChange(): void
    {
        $new_value = [
            'content' => '1',
            'format'  => 'text',
        ];

        self::assertFalse(
            $this->text_field->hasChanges(ArtifactTestBuilder::anArtifact(987)->build(), $this->previous_value, $new_value)
        );
    }

    public function testItReturnsFalseIfOnlyTheFormatChanged(): void
    {
        $new_value = [
            'content' => '1',
            'format'  => 'html',
        ];

        self::assertFalse(
            $this->text_field->hasChanges(ArtifactTestBuilder::anArtifact(987)->build(), $this->previous_value, $new_value)
        );
    }

    public function testItReturnsValueItSelfIfItWellFormatted(): void
    {
        $value = [
            'content' => 'I am happy because I am well formatted',
            'format'  => Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT,
        ];

        self::assertEquals($value, $this->text_field->getRestFieldData($value));
    }

    public function testItReturnsTheContentAndTheUserDefaultFormatIfTheGivenFormatIsInvalid(): void
    {
        $content = 'I am sad because I am not well formatted :( ';
        $value   = [
            'content' => $content,
            'format'  => 'indignity_format',
        ];

        $this->user->setPreference(PFUser::EDITION_DEFAULT_FORMAT, 'commonmark');
        $this->text_field->method('getProperty')->with('default_value')->willReturn('wololo');

        $rest_field_data = $this->text_field->getRestFieldData($value);

        self::assertEquals(Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT, $rest_field_data['format']);
        self::assertEquals($content, $rest_field_data['content']);
    }

    public function testItReturnsTheContentAndTheUserDefaultFormatIffValueIsNotAnArray(): void
    {
        $value = 'I am sad because :(';

        $this->user->setPreference(PFUser::EDITION_DEFAULT_FORMAT, 'commonmark');
        $this->text_field->method('getProperty')->with('default_value')->willReturn('wololo');

        $rest_field_data = $this->text_field->getRestFieldData($value);

        self::assertEquals(Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT, $rest_field_data['format']);
        self::assertEquals($value, $rest_field_data['content']);
    }
}
