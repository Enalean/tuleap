<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\Date;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use SimpleXMLElement;
use TestHelper;
use Tracker_Artifact_ChangesetValue_Date;
use Tracker_FormElement_DateFormatter;
use Tracker_FormElement_RESTValueByField_NotImplementedException;
use Tracker_Report_Criteria;
use Tracker_Report_REST;
use Tuleap\Date\TimezoneWrapper;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Semantic\Timeframe\ArtifactTimeframeHelper;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueDateTestBuilder;
use Tuleap\Tracker\Test\Builders\ReportTestBuilder;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use XMLImportHelper;

#[DisableReturnValueGenerationForTestDoubles]
final class DateFieldTest extends TestCase
{
    use GlobalResponseMock;
    use GlobalLanguageMock;

    private function getDateField(): DateField&MockObject
    {
        return $this->createPartialMock(DateField::class, [
            'getProperty', 'getValueDao', 'isRequired', '_getUserCSVDateFormat', 'getArtifactTimeframeHelper', 'getProperties',
        ]);
    }

    public function testNoDefaultValue(): void
    {
        $date_field = $this->getDateField();
        $date_field->method('getProperty')->with('default_value')->willReturn(null);
        self::assertFalse($date_field->hasDefaultValue());
    }

    public function testDefaultValue(): void
    {
        TimezoneWrapper::wrapTimezone(
            'UTC',
            function () {
                $date_field = $this->getDateField();
                $date_field->method('getProperty')->willReturnCallback(static fn(string $key) => match ($key) {
                    'default_value_type' => 1,
                    'default_value'      => '1234567890',
                    'display_time'       => 0,
                });
                self::assertTrue($date_field->hasDefaultValue());
                self::assertEquals('2009-02-13', $date_field->getDefaultValue());
            }
        );
    }

    public function testToday(): void
    {
        $date_field = $this->getDateField();
        $date_field->method('getProperty')->willReturnCallback(static fn(string $key) => match ($key) {
            'default_value_type' => '0',
            'default_value'      => '1234567890',
            'display_time'       => 0,
        });
        self::assertTrue($date_field->hasDefaultValue());
        self::assertEquals(
            (new DateTimeImmutable())->format(Tracker_FormElement_DateFormatter::DATE_FORMAT),
            $date_field->getDefaultValue(),
        );
    }

    public function testItDisplayTime(): void
    {
        $date_field = $this->getDateField();
        $date_field->method('getProperty')->willReturnCallback(static fn(string $key) => match ($key) {
            'display_time' => 1,
        });

        self::assertTrue($date_field->isTimeDisplayed());
    }

    public function testItDontDisplayTime(): void
    {
        $date_field = $this->getDateField();
        $date_field->method('getProperty')->willReturnCallback(static fn(string $key) => match ($key) {
            'display_time' => 0,
        });

        self::assertFalse($date_field->isTimeDisplayed());
    }

    public function testGetChangesetValue(): void
    {
        $value_dao = $this->createMock(DateValueDao::class);
        $dar       = TestHelper::arrayToDar(['id' => 123, 'field_id' => 1, 'value' => '1221221466']);
        $value_dao->method('searchById')->willReturn($dar);

        $date_field = $this->getDateField();
        $date_field->method('getValueDao')->willReturn($value_dao);

        self::assertInstanceOf(
            Tracker_Artifact_ChangesetValue_Date::class,
            $date_field->getChangesetValue(ChangesetTestBuilder::aChangeset(65)->build(), 123, false),
        );
    }

    public function testGetChangesetValueDoesntExist(): void
    {
        $value_dao = $this->createMock(DateValueDao::class);
        $dar       = TestHelper::arrayToDar(false);
        $value_dao->method('searchById')->willReturn($dar);

        $date_field = $this->getDateField();
        $date_field->method('getValueDao')->willReturn($value_dao);

        self::assertNull($date_field->getChangesetValue(null, 123, false));
    }

    public function testIsValidRequiredField(): void
    {
        $field = $this->getDateField();
        $field->method('isRequired')->willReturn(true);
        $field->method('getProperty')->with('display_time')->willReturn(0);
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();
        self::assertTrue($field->isValid($artifact, '2009-08-31'));
        self::assertFalse($field->isValid($artifact, '2009-08-45'));
        self::assertFalse($field->isValid($artifact, '2009-13-06'));
        self::assertFalse($field->isValid($artifact, '20091306'));
        self::assertFalse($field->isValid($artifact, '06/12/2009'));
        self::assertFalse($field->isValid($artifact, '06-12-2009'));
        self::assertFalse($field->isValid($artifact, 'foobar'));
        self::assertFalse($field->isValid($artifact, 06 / 12 / 2009));
        self::assertFalse($field->isValidRegardingRequiredProperty($artifact, ''));
        self::assertFalse($field->isValidRegardingRequiredProperty($artifact, null));
    }

    public function testIsValidNotRequiredField(): void
    {
        $field = $this->getDateField();
        $field->method('isRequired')->willReturn(false);
        $field->method('getProperty')->with('display_time')->willReturn(0);
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();
        self::assertTrue($field->isValid($artifact, ''));
        self::assertTrue($field->isValid($artifact, null));
    }

    public function testGetFieldData(): void
    {
        $field = $this->getDateField();
        self::assertEquals('2010-04-30', $field->getFieldData('2010-04-30'));
        self::assertEquals('2010-04-30 6:08', $field->getFieldData('2010-04-30 6:08'));
        self::assertNull($field->getFieldData('03-04-2010'));
    }

    public function testGetFieldDataAsTimestamp(): void
    {
        $field = $this->getDateField();
        $field->method('getProperty')->with('display_time')->willReturn(0);
        self::assertEquals('2010-04-30', $field->getFieldData((string) mktime(5, 3, 2, 4, 30, 2010)));
        self::assertNull($field->getFieldData('1.5'));
    }

    public function testGetEmptyFieldData(): void
    {
        $field = $this->getDateField();

        self::assertEquals('', $field->getFieldData(''));
    }

    public function testGetFieldDataReturnsNullWhenInvalidInputIsProvided(): void
    {
        $field = $this->getDateField();

        self::assertNull($field->getFieldData('foo'));
    }

    public function testGetFieldDataForCSVPreview(): void
    {
        $field = $this->getDateField();
        $field->method('getProperty')->with('display_time')->willReturn(0);
        $field->method('_getUserCSVDateFormat')->willReturn('day_month_year');
        self::assertEquals('1981-04-25', $field->getFieldDataForCSVPreview('25/04/1981'));
        self::assertNull($field->getFieldDataForCSVPreview('35/44/1981'));  // this function checks date validity!
        self::assertNull($field->getFieldDataForCSVPreview(''));

        $other_field = $this->getDateField();
        $other_field->method('getProperty')->with('display_time')->willReturn(0);
        $other_field->method('_getUserCSVDateFormat')->willReturn('month_day_year');
        self::assertEquals('1981-04-25', $other_field->getFieldDataForCSVPreview('04/25/1981'));
    }

    public function testExplodeXlsDateFmtDDMMYYYY(): void
    {
        $field = $this->getDateField();
        $field->method('getProperty')->with('display_time')->willReturn(0);
        $field->method('_getUserCSVDateFormat')->willReturn('day_month_year');
        self::assertEquals(['1981', '04', '25', '0', '0', '0'], $field->explodeXlsDateFmt('25/04/1981'));
        self::assertEquals([], $field->explodeXlsDateFmt('04/25/1981'));
        self::assertEquals([], $field->explodeXlsDateFmt('04/25/81'));
        self::assertEquals([], $field->explodeXlsDateFmt('25/04/81'));
        self::assertEquals([], $field->explodeXlsDateFmt('25/04/81 10AM'));
    }

    public function testExplodeXlsDateFmtMMDDYYYY(): void
    {
        $field = $this->getDateField();
        $field->method('getProperty')->with('display_time')->willReturn(0);
        $field->method('_getUserCSVDateFormat')->willReturn('month_day_year');
        self::assertEquals(['1981', '04', '25', '0', '0', '0'], $field->explodeXlsDateFmt('04/25/1981'));
        self::assertEquals([], $field->explodeXlsDateFmt('25/04/1981'));
        self::assertEquals([], $field->explodeXlsDateFmt('25/04/81'));
        self::assertEquals([], $field->explodeXlsDateFmt('04/25/81'));
        self::assertEquals([], $field->explodeXlsDateFmt('04/25/81 10AM'));
    }

    public function testItExplodesDateWithHoursInDDMMYYYYFormat(): void
    {
        $field = $this->getDateField();
        $field->method('_getUserCSVDateFormat')->willReturn('day_month_year');

        self::assertEquals(
            ['1981', '04', '25', '10', '00', '00'],
            $field->explodeXlsDateFmt('25/04/1981 10:00:01')
        );
    }

    public function testItExplodesDateWithHoursInMMDDYYYYFormat(): void
    {
        $field = $this->getDateField();
        $field->method('_getUserCSVDateFormat')->willReturn('month_day_year');

        self::assertEquals(
            ['1981', '04', '25', '01', '02', '00'],
            $field->explodeXlsDateFmt('04/25/1981 01:02:03')
        );
    }

    public function testItExplodesDateWithHoursInMMDDYYYYHHSSFormatWithoutGivenSeconds(): void
    {
        $field = $this->getDateField();
        $field->method('_getUserCSVDateFormat')->willReturn('month_day_year');

        self::assertEquals(
            ['1981', '04', '25', '01', '02', '00'],
            $field->explodeXlsDateFmt('04/25/1981 01:02')
        );
    }

    public function testNbDigits(): void
    {
        $field = $this->getDateField();
        self::assertEquals(1, $field->_nbDigits(1));
        self::assertEquals(2, $field->_nbDigits(15));
        self::assertEquals(3, $field->_nbDigits(101));
        self::assertEquals(4, $field->_nbDigits(1978));
        self::assertEquals(5, $field->_nbDigits(12345));
        self::assertEquals(1, $field->_nbDigits(001));
        self::assertEquals(1, $field->_nbDigits('001'));
    }

    public function testExportPropertiesToXMLNoDefaultValue(): void
    {
        $file     = __DIR__ . '/../../../_fixtures/FieldDate/ImportTrackerFormElementDatePropertiesNoDefaultValueTest.xml';
        $xml_test = simplexml_load_string(file_get_contents($file), SimpleXMLElement::class, LIBXML_NOENT);

        $date_field = $this->getDateField();
        $properties = [
            'default_value_type' => [
                'type'    => 'radio',
                'value'   => 1,    // specific date
                'choices' => [
                    'default_value_today' => [
                        'radio_value' => 0,
                        'type'        => 'label',
                        'value'       => 'today',
                    ],
                    'default_value'       => [
                        'radio_value' => 1,
                        'type'        => 'date',
                        'value'       => '',          // no default value
                    ],
                ],
            ],
        ];
        $date_field->method('getProperties')->willReturn($properties);
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $date_field->exportPropertiesToXML($root);
        self::assertEquals((string) $xml_test->properties, (string) $root->properties);
        self::assertEquals(0, count($root->properties->attributes()));
    }

    public function testExportPropertiesToXMLNoDefaultValue2(): void
    {
        // another test if value = '0' instead of ''
        $file     = __DIR__ . '/../../../_fixtures/FieldDate/ImportTrackerFormElementDatePropertiesNoDefaultValueTest.xml';
        $xml_test = simplexml_load_string(file_get_contents($file), SimpleXMLElement::class, LIBXML_NOENT);

        $date_field = $this->getDateField();
        $properties = [
            'default_value_type' => [
                'type'    => 'radio',
                'value'   => 1,    // specific date
                'choices' => [
                    'default_value_today' => [
                        'radio_value' => 0,
                        'type'        => 'label',
                        'value'       => 'today',
                    ],
                    'default_value'       => [
                        'radio_value' => 1,
                        'type'        => 'date',
                        'value'       => '0',          // no default value
                    ],
                ],
            ],
        ];
        $date_field->method('getProperties')->willReturn($properties);
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $date_field->exportPropertiesToXML($root);
        self::assertEquals((string) $xml_test->properties, (string) $root->properties);
        self::assertEquals(0, count($root->properties->attributes()));
    }

    public function testExportPropertiesToXMLDefaultValueToday(): void
    {
        $file     = __DIR__ . '/../../../_fixtures/FieldDate/ImportTrackerFormElementDatePropertiesDefaultValueTodayTest.xml';
        $xml_test = simplexml_load_string(file_get_contents($file), SimpleXMLElement::class, LIBXML_NOENT);

        $date_field = $this->getDateField();
        $properties = [
            'default_value_type' => [
                'type'    => 'radio',
                'value'   => 0,    // today
                'choices' => [
                    'default_value_today' => [
                        'radio_value' => 0,
                        'type'        => 'label',
                        'value'       => 'today',
                    ],
                    'default_value'       => [
                        'radio_value' => 1,
                        'type'        => 'date',
                        'value'       => '1234567890',
                    ],
                ],
            ],
        ];
        $date_field->method('getProperties')->willReturn($properties);
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $date_field->exportPropertiesToXML($root);
        self::assertEquals((string) $xml_test->properties, (string) $root->properties);
        self::assertEquals(1, count($root->properties->attributes()));
        $attr = $root->properties->attributes();
        self::assertEquals('today', ((string) $attr->default_value));
    }

    public function testExportPropertiesToXMLDefaultValueSpecificDate(): void
    {
        $file     = __DIR__ . '/../../../_fixtures/FieldDate/ImportTrackerFormElementDatePropertiesDefaultValueSpecificDateTest.xml';
        $xml_test = simplexml_load_string(file_get_contents($file), SimpleXMLElement::class, LIBXML_NOENT);

        $date_field = $this->getDateField();
        $properties = [
            'default_value_type' => [
                'type'    => 'radio',
                'value'   => 1,    // specific date
                'choices' => [
                    'default_value_today' => [
                        'radio_value' => 0,
                        'type'        => 'label',
                        'value'       => 'today',
                    ],
                    'default_value'       => [
                        'radio_value' => 1,
                        'type'        => 'date',
                        'value'       => '1234567890',  // specific date
                    ],
                ],
            ],
        ];
        $date_field->method('getProperties')->willReturn($properties);
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $date_field->exportPropertiesToXML($root);
        self::assertEquals((string) $xml_test->properties, (string) $root->properties);
        self::assertEquals(1, count($root->properties->attributes()));
        $attr = $root->properties->attributes();
        self::assertEquals('1234567890', ((string) $attr->default_value));
    }

    public function testExportPropertiesToXMLDisplayTime(): void
    {
        $file     = __DIR__ . '/../../../_fixtures/FieldDate/ImportTrackerFormElementDatePropertiesDisplayTime.xml';
        $xml_test = simplexml_load_string(file_get_contents($file), SimpleXMLElement::class, LIBXML_NOENT);

        $date_field = $this->getDateField();
        $properties = [
            'display_time' => [
                'type'  => 'checkbox',
                'value' => 1,
            ],
        ];

        $date_field->method('getProperties')->willReturn($properties);
        $date_field->method('getProperty')->with('display_time')->willReturn(1);

        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $date_field->exportPropertiesToXML($root);
        self::assertEquals((string) $xml_test->properties, (string) $root->properties);
        self::assertEquals(1, count($root->properties->attributes()));
        $attr = $root->properties->attributes();
        self::assertEquals('1', ((string) $attr->display_time));
    }

    public function testExportPropertiesToXMLDisplayTimeWhenDisplayTimeIsZero(): void
    {
        $file     = __DIR__ . '/../../../_fixtures/FieldDate/ImportTrackerFormElementDatePropertiesDisplayTimeZero.xml';
        $xml_test = simplexml_load_string(file_get_contents($file), SimpleXMLElement::class, LIBXML_NOENT);

        $date_field = $this->getDateField();
        $properties = [
            'display_time' => [
                'type'  => 'checkbox',
                'value' => 0,
            ],
        ];

        $date_field->method('getProperties')->willReturn($properties);
        $date_field->method('getProperty')->with('display_time')->willReturn(0);

        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $date_field->exportPropertiesToXML($root);
        self::assertEquals((string) $xml_test->properties, (string) $root->properties);
        self::assertEquals(1, count($root->properties->attributes()));
        $attr = $root->properties->attributes();
        self::assertEquals('0', ((string) $attr->display_time));
    }

    public function testImportRealdate(): void
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" standalone="yes"?>
            <formElement type="date" ID="F0" rank="20" required="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
                <properties default_value="1234564290" />
            </formElement>'
        );

        $mapping = [];

        $date = $this->getDateField();
        $date->method('getProperty')->willReturnCallback(static fn(string $key) => match ($key) {
            'display_time'       => 0,
            'default_value_type' => 1,
            'default_value'      => 1234564290,
        });

        $feedback_collector = new TrackerXmlImportFeedbackCollector();

        $date->continueGetInstanceFromXML($xml, $mapping, $this->createStub(XMLImportHelper::class), $feedback_collector);

        self::assertEquals('2009-02-13', $date->getDefaultValue());
    }

    public function testImportToday(): void
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" standalone="yes"?>
            <formElement type="date" ID="F0" rank="20" required="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
                <properties default_value="today" />
            </formElement>'
        );

        $mapping = [];

        $date = $this->getDateField();
        $date->method('getProperty')->willReturnCallback(static fn(string $key) => match ($key) {
            'display_time'       => 0,
            'default_value_type' => 0,
            'default_value'      => '',
        });

        $feedback_collector = new TrackerXmlImportFeedbackCollector();

        $date->continueGetInstanceFromXML($xml, $mapping, $this->createStub(XMLImportHelper::class), $feedback_collector);
        self::assertEquals(
            (new DateTimeImmutable())->format(Tracker_FormElement_DateFormatter::DATE_FORMAT),
            $date->getDefaultValue(),
        );
    }

    public function testImportNodefault(): void
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" standalone="yes"?>
            <formElement type="date" ID="F0" rank="20" required="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
                <properties />
            </formElement>'
        );

        $mapping = [];

        $date = $this->getDateField();
        $date->method('getProperty')->willReturnCallback(static fn(string $key) => match ($key) {
            'display_time'       => 0,
            'default_value_type' => 1,
            'default_value'      => '',
        });

        $feedback_collector = new TrackerXmlImportFeedbackCollector();

        $date->continueGetInstanceFromXML($xml, $mapping, $this->createStub(XMLImportHelper::class), $feedback_collector);
        self::assertEqualS('', $date->getDefaultValue());
    }

    public function testImportEmpty(): void
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" standalone="yes"?>
            <formElement type="date" ID="F0" rank="20" required="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
                <properties default_value="" />
            </formElement>'
        );

        $mapping = [];

        $date = $this->getDateField();
        $date->method('getProperty')->willReturnCallback(static fn(string $key) => match ($key) {
            'display_time'       => 0,
            'default_value_type' => 1,
            'default_value'      => '',
        });

        $feedback_collector = new TrackerXmlImportFeedbackCollector();

        $date->continueGetInstanceFromXML($xml, $mapping, $this->createStub(XMLImportHelper::class), $feedback_collector);
        self::assertEquals('', $date->getDefaultValue());
    }

    public function testFieldDateShouldSendEmptyMailValueWhenValueIsEmpty(): void
    {
        $user     = UserTestBuilder::buildWithDefaults();
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();
        $date     = $this->getDateField();
        self::assertEquals('-', $date->fetchMailArtifactValue($artifact, $user, false, null, 'html'));
    }

    public function testFieldDateShouldSendAMailWithAReadableDateEnUS(): void
    {
        $GLOBALS['Language']->method('getText')->with('system', 'datefmt_short')->willReturn('Y-m-d');
        $GLOBALS['Language']->method('getLanguageFromAcceptLanguage')->willReturn('en_US');

        $user     = UserTestBuilder::buildWithDefaults();
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();
        $date     = $this->getDateField();
        $date->method('getProperty')->with('display_time')->willReturn(0);
        $timeframe_helper = $this->createMock(ArtifactTimeframeHelper::class);
        $timeframe_helper->method('artifactHelpShouldBeShownToUser')->willReturn(false);
        $date->method('getArtifactTimeframeHelper')->willReturn($timeframe_helper);

        $value = ChangesetValueDateTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(123)->build(), $date)->withTimestamp(1322752769)->build();

        self::assertEquals('2011-12-01', $date->fetchMailArtifactValue($artifact, $user, false, $value, 'text'));
        self::assertEquals('2011-12-01', $date->fetchMailArtifactValue($artifact, $user, false, $value, 'html'));
    }

    public function testFieldDateShouldSendAMailWithAReadableDatefrFR(): void
    {
        $GLOBALS['Language']->method('getText')->with('system', 'datefmt_short')->willReturn('d/m/Y');
        $GLOBALS['Language']->method('getLanguageFromAcceptLanguage')->willReturn('fr_FR');

        $user     = UserTestBuilder::buildWithDefaults();
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();
        $date     = $this->getDateField();
        $date->method('getProperty')->with('display_time')->willReturn(0);
        $timeframe_helper = $this->createMock(ArtifactTimeframeHelper::class);
        $timeframe_helper->method('artifactHelpShouldBeShownToUser')->willReturn(false);
        $date->method('getArtifactTimeframeHelper')->willReturn($timeframe_helper);

        $value = ChangesetValueDateTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(123)->build(), $date)->withTimestamp(1322752769)->build();

        self::assertEquals('01/12/2011', $date->fetchMailArtifactValue($artifact, $user, false, $value, 'text'));
        self::assertEquals('01/12/2011', $date->fetchMailArtifactValue($artifact, $user, false, $value, 'html'));
    }

    public function testFieldDateShouldSendEmptyMailWhenThereIsNoDateDefined(): void
    {
        $user     = UserTestBuilder::buildWithDefaults();
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();
        $date     = $this->getDateField();

        $value = ChangesetValueDateTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(123)->build(), $date)->withTimestamp(0)->build();

        self::assertEquals('-', $date->fetchMailArtifactValue($artifact, $user, false, $value, 'text'));
        self::assertEquals('-', $date->fetchMailArtifactValue($artifact, $user, false, $value, 'html'));
    }

    public function testItThrowsAnExceptionWhenReturningValueIndexedByFieldName(): void
    {
        $field = $this->getDateField();

        $this->expectException(Tracker_FormElement_RESTValueByField_NotImplementedException::class);

        $value = ['some_value'];

        $field->getFieldDataFromRESTValueByField($value);
    }

    public function testItReturnsTheCorrectCriteriaForBetween(): void
    {
        $is_advanced = true;
        $column      = 'my_date_column';
        $from        = strtotime('2014-07-05');
        $to          = strtotime('2014-07-07');

        $field      = $this->getDateField();
        $reflection = new ReflectionClass($field::class);
        $method     = $reflection->getMethod('getSQLCompareDate');
        $method->setAccessible(true);

        $field    = $this->getDateField();
        $fragment = $method->invokeArgs($field, [$is_advanced, '=', $from, $to, $column]);
        self::assertEquals('my_date_column BETWEEN ? AND ?', $fragment->sql);
        self::assertEquals([$from, $to + 86400 - 1], $fragment->parameters);
    }

    public function testItReturnsTheCorrectCriteriaForBeforeIncludingTheToDay(): void
    {
        $is_advanced = true;
        $column      = 'my_date_column';
        $to          = strtotime('2014-07-07');

        $field      = $this->getDateField();
        $reflection = new ReflectionClass($field::class);
        $method     = $reflection->getMethod('getSQLCompareDate');
        $method->setAccessible(true);

        $fragment = $method->invokeArgs($field, [$is_advanced, '=', null, $to, $column]);
        self::assertEquals('my_date_column <= ?', $fragment->sql);
        self::assertEquals([$to + 86400 - 1], $fragment->parameters);
    }

    public function testItReturnsTheCorrectCriteriaForEquals(): void
    {
        $is_advanced = false;
        $column      = 'my_date_column';
        $from        = null;
        $to          = strtotime('2014-07-07');

        $field      = $this->getDateField();
        $reflection = new ReflectionClass($field::class);
        $method     = $reflection->getMethod('getSQLCompareDate');
        $method->setAccessible(true);

        $fragment = $method->invokeArgs($field, [$is_advanced, '=', $from, $to, $column]);
        self::assertEquals('my_date_column BETWEEN ? AND ?', $fragment->sql);
        self::assertEquals([$to, $to + 86400 - 1], $fragment->parameters);
    }

    public function testItReturnsTheCorrectCriteriaForBefore(): void
    {
        $is_advanced = false;
        $column      = 'my_date_column';
        $from        = null;
        $to          = strtotime('2014-07-07');

        $field      = $this->getDateField();
        $reflection = new ReflectionClass($field::class);
        $method     = $reflection->getMethod('getSQLCompareDate');
        $method->setAccessible(true);

        $fragment = $method->invokeArgs($field, [$is_advanced, '<', $from, $to, $column]);
        self::assertEquals('my_date_column < ?', $fragment->sql);
        self::assertEquals([$to], $fragment->parameters);
    }

    public function testItReturnsTheCorrectCriteriaForAfter(): void
    {
        $is_advanced = false;
        $column      = 'my_date_column';
        $from        = null;
        $to          = strtotime('2014-07-07');

        $field      = $this->getDateField();
        $reflection = new ReflectionClass($field::class);
        $method     = $reflection->getMethod('getSQLCompareDate');
        $method->setAccessible(true);

        $fragment = $method->invokeArgs($field, [$is_advanced, '>', $from, $to, $column]);
        self::assertEquals('my_date_column > ?', $fragment->sql);
        self::assertEquals([$to + 86400 - 1], $fragment->parameters);
    }

    public function testItAddsAnEqualsCrterion(): void
    {
        $date     = '2014-04-05';
        $field    = $this->getDateField();
        $criteria = new Tracker_Report_Criteria(12, ReportTestBuilder::aPublicReport()->withId(1)->build(), $field, 24, false);
        $values   = [
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => $date,
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_EQUALS,
        ];

        $field->setCriteriaValueFromREST($criteria, $values);
        $res = $field->getCriteriaValue($criteria);

        self::assertCount(3, $res);
        self::assertEquals('=', $res['op']);
        self::assertEquals(0, $res['from_date']);
        self::assertEquals(strtotime($date), $res['to_date']);
    }

    public function testItAddsAGreaterThanCrterion(): void
    {
        $date     = '2014-04-05T00:00:00-05:00';
        $field    = $this->getDateField();
        $criteria = new Tracker_Report_Criteria(12, ReportTestBuilder::aPublicReport()->withId(1)->build(), $field, 24, false);
        $values   = [
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => $date,
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_GREATER_THAN,
        ];

        $field->setCriteriaValueFromREST($criteria, $values);
        $res = $field->getCriteriaValue($criteria);

        self::assertCount(3, $res);
        self::assertEquals('>', $res['op']);
        self::assertEquals(0, $res['from_date']);
        self::assertEquals(strtotime($date), $res['to_date']);
    }

    public function testItAddsALessThanCrterion(): void
    {
        $date     = '2014-04-05';
        $field    = $this->getDateField();
        $criteria = new Tracker_Report_Criteria(12, ReportTestBuilder::aPublicReport()->withId(1)->build(), $field, 24, false);
        $values   = [
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => [$date],
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_LESS_THAN,
        ];

        $field->setCriteriaValueFromREST($criteria, $values);
        $res = $field->getCriteriaValue($criteria);

        self::assertCount(3, $res);
        self::assertEquals('<', $res['op']);
        self::assertEquals(0, $res['from_date']);
        self::assertEquals(strtotime($date), $res['to_date']);
    }

    public function testItAddsABetweenCrterion(): void
    {
        $from_date = '2014-04-05';
        $to_date   = '2014-05-12';
        $field     = $this->getDateField();
        $criteria  = new Tracker_Report_Criteria(12, ReportTestBuilder::aPublicReport()->withId(1)->build(), $field, 24, false);
        $values    = [
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => [$from_date, $to_date],
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_BETWEEN,
        ];

        $field->setCriteriaValueFromREST($criteria, $values);
        $res = $field->getCriteriaValue($criteria);

        self::assertCount(3, $res);
        self::assertEquals('=', $res['op']);
        self::assertEquals(strtotime($from_date), $res['from_date']);
        self::assertEquals(strtotime($to_date), $res['to_date']);
    }

    public function testItIgnoresInvalidDates(): void
    {
        $date = 'christmas eve';

        $field    = $this->getDateField();
        $criteria = new Tracker_Report_Criteria(12, ReportTestBuilder::aPublicReport()->withId(1)->build(), $field, 24, false);
        $values   = [
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => $date,
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_BETWEEN,
        ];

        $res = $field->setCriteriaValueFromREST($criteria, $values);
        self::assertFalse($res);
    }
}
