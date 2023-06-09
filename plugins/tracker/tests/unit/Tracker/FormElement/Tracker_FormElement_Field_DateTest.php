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

namespace Tuleap\Tracker\FormElement;

use BaseLanguage;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use SimpleXMLElement;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Date;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_RESTValueByField_NotImplementedException;
use Tracker_Report;
use Tracker_Report_Criteria;
use Tracker_Report_REST;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Tracker\FormElement\Field\Date\DateValueDao;
use Tuleap\Tracker\Semantic\Timeframe\ArtifactTimeframeHelper;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use XMLImportHelper;

final class Tracker_FormElement_Field_DateTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use MockeryPHPUnitIntegration;
    use GlobalResponseMock;
    use GlobalLanguageMock;

    /**
     * @return \Mockery\Mock|Tracker_FormElement_Field_Date
     */
    private function getDateField()
    {
        return \Mockery::mock(Tracker_FormElement_Field_Date::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    public function testNoDefaultValue(): void
    {
        $date_field = $this->getDateField();
        $date_field->shouldReceive('getProperty')->withArgs(['default_value'])->andReturn(null);
        $this->assertFalse($date_field->hasDefaultValue());
    }

    public function testDefaultValue(): void
    {
        $date_field  = $this->getDateField();
        $custom_date = 1;
        $date_field->shouldReceive('getProperty')->withArgs(['default_value_type'])->andReturn($custom_date);
        $date_field->shouldReceive('getProperty')->withArgs(['default_value'])->andReturn('1234567890');
        $date_field->shouldReceive('formatDate')->withArgs(['1234567890'])->andReturn('2009-02-13');
        $this->assertTrue($date_field->hasDefaultValue());
        $this->assertEquals('2009-02-13', $date_field->getDefaultValue());
    }

    public function testToday(): void
    {
        $date_field = $this->getDateField();
        $today      = '0';
        $date_field->shouldReceive('getProperty')->withArgs(['default_value_type'])->andReturn($today);
        $date_field->shouldReceive('getProperty')->withArgs(['default_value'])->andReturn('1234567890');
        $date_field->shouldReceive('formatDate')->withArgs(['1234567890'])->andReturn('2009-02-13');
        $date_field->shouldReceive('formatDate')->withArgs([$_SERVER['REQUEST_TIME']])->andReturn('date-of-today');
        $this->assertTrue($date_field->hasDefaultValue());
        $this->assertEquals('date-of-today', $date_field->getDefaultValue());
    }

    public function testItDisplayTime(): void
    {
        $date_field = $this->getDateField();
        $date_field->shouldReceive('getProperty')->withArgs(['display_time'])->andReturn(1);

        $this->assertTrue($date_field->isTimeDisplayed());
    }

    public function testItDontDisplayTime(): void
    {
        $date_field = $this->getDateField();
        $date_field->shouldReceive('getProperty')->withArgs(['display_time'])->andReturn(0);

        $this->assertFalse($date_field->isTimeDisplayed());
    }

    public function testGetChangesetValue(): void
    {
        $value_dao = Mockery::mock(DateValueDao::class);
        $dar       = \TestHelper::arrayToDar(['id' => 123, 'field_id' => 1, 'value' => '1221221466']);
        $value_dao->shouldReceive('searchById')->andReturn($dar);

        $date_field = $this->getDateField();
        $date_field->shouldReceive('getValueDao')->andReturn($value_dao);

        $this->assertInstanceOf(
            Tracker_Artifact_ChangesetValue_Date::class,
            $date_field->getChangesetValue(Mockery::mock(Tracker_Artifact_Changeset::class), 123, false),
        );
    }

    public function testGetChangesetValueDoesntExist(): void
    {
        $value_dao = Mockery::mock(DateValueDao::class);
        $dar       = \TestHelper::arrayToDar(false);
        $value_dao->shouldReceive('searchById')->andReturn($dar);

        $date_field = $this->getDateField();
        $date_field->shouldReceive('getValueDao')->andReturn($value_dao);

        $this->assertNull($date_field->getChangesetValue(null, 123, false));
    }

    public function testIsValidRequiredField(): void
    {
        $field = $this->getDateField();
        $field->shouldReceive('isRequired')->andReturn(true);
        $field->shouldReceive('getProperty')->withArgs(['display_time']);
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->assertTrue($field->isValid($artifact, '2009-08-31'));
        $this->assertFalse($field->isValid($artifact, '2009-08-45'));
        $this->assertFalse($field->isValid($artifact, '2009-13-06'));
        $this->assertFalse($field->isValid($artifact, '20091306'));
        $this->assertFalse($field->isValid($artifact, '06/12/2009'));
        $this->assertFalse($field->isValid($artifact, '06-12-2009'));
        $this->assertFalse($field->isValid($artifact, 'foobar'));
        $this->assertFalse($field->isValid($artifact, 06 / 12 / 2009));
        $this->assertFalse($field->isValidRegardingRequiredProperty($artifact, ''));
        $this->assertFalse($field->isValidRegardingRequiredProperty($artifact, null));
    }

    public function testIsValidNotRequiredField(): void
    {
        $field = $this->getDateField();
        $field->shouldReceive('isRequired')->andReturn(false);
        $field->shouldReceive('getProperty')->withArgs(['display_time']);
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->assertTrue($field->isValid($artifact, ''));
        $this->assertTrue($field->isValid($artifact, null));
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
        $field->shouldReceive('getProperty')->withArgs(['display_time']);
        $this->assertEquals($field->getFieldData((string) mktime(5, 3, 2, 4, 30, 2010)), '2010-04-30');
        $this->assertNull($field->getFieldData('1.5'));
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
        $field->shouldReceive('getProperty')->withArgs(['display_time']);
        $field->shouldReceive('_getUserCSVDateFormat')->andReturn('day_month_year');
        $this->assertEquals('1981-04-25', $field->getFieldDataForCSVPreview('25/04/1981'));
        $this->assertNull($field->getFieldDataForCSVPreview('35/44/1981'));  // this function check date validity!
        $this->assertNull($field->getFieldDataForCSVPreview(''));

        $other_field = $this->getDateField();
        $other_field->shouldReceive('getProperty')->withArgs(['display_time']);
        $other_field->shouldReceive('_getUserCSVDateFormat')->andReturn('month_day_year');
        $this->assertEquals('1981-04-25', $other_field->getFieldDataForCSVPreview('04/25/1981'));
    }

    public function testExplodeXlsDateFmtDDMMYYYY(): void
    {
        $field = $this->getDateField();
        $field->shouldReceive('getProperty')->withArgs(['display_time']);
        $field->shouldReceive('_getUserCSVDateFormat')->andReturn('day_month_year');
        $this->assertEquals(['1981', '04', '25', '0', '0', '0'], $field->explodeXlsDateFmt('25/04/1981'));
        $this->assertEquals([], $field->explodeXlsDateFmt('04/25/1981'));
        $this->assertEquals([], $field->explodeXlsDateFmt('04/25/81'));
        $this->assertEquals([], $field->explodeXlsDateFmt('25/04/81'));
        $this->assertEquals([], $field->explodeXlsDateFmt('25/04/81 10AM'));
    }

    public function testExplodeXlsDateFmtMMDDYYYY(): void
    {
        $field = $this->getDateField();
        $field->shouldReceive('getProperty')->withArgs(['display_time']);
        $field->shouldReceive('_getUserCSVDateFormat')->andReturn('month_day_year');
        $this->assertEquals(['1981', '04', '25', '0', '0', '0'], $field->explodeXlsDateFmt('04/25/1981'));
        $this->assertEquals([], $field->explodeXlsDateFmt('25/04/1981'));
        $this->assertEquals([], $field->explodeXlsDateFmt('25/04/81'));
        $this->assertEquals([], $field->explodeXlsDateFmt('04/25/81'));
        $this->assertEquals([], $field->explodeXlsDateFmt('04/25/81 10AM'));
    }

    public function testItExplodesDateWithHoursInDDMMYYYYFormat(): void
    {
        $field = $this->getDateField();
        $field->shouldReceive('_getUserCSVDateFormat')->andReturn('day_month_year');

        $this->assertEquals(
            ['1981', '04', '25', '10', '00', '00'],
            $field->explodeXlsDateFmt('25/04/1981 10:00:01')
        );
    }

    public function testItExplodesDateWithHoursInMMDDYYYYFormat(): void
    {
        $field = $this->getDateField();
        $field->shouldReceive('_getUserCSVDateFormat')->andReturn('month_day_year');

        $this->assertEquals(
            ['1981', '04', '25', '01', '02', '00'],
            $field->explodeXlsDateFmt('04/25/1981 01:02:03')
        );
    }

    public function testItExplodesDateWithHoursInMMDDYYYYHHSSFormatWithoutGivenSeconds(): void
    {
        $field = $this->getDateField();
        $field->shouldReceive('_getUserCSVDateFormat')->andReturn('month_day_year');

        $this->assertEquals(
            ['1981', '04', '25', '01', '02', '00'],
            $field->explodeXlsDateFmt('04/25/1981 01:02')
        );
    }

    public function testNbDigits(): void
    {
        $field = $this->getDateField();
        $this->assertEquals(1, $field->_nbDigits(1));
        $this->assertEquals(2, $field->_nbDigits(15));
        $this->assertEquals(3, $field->_nbDigits(101));
        $this->assertEquals(4, $field->_nbDigits(1978));
        $this->assertEquals(5, $field->_nbDigits(12345));
        $this->assertEquals(1, $field->_nbDigits(001));
        $this->assertEquals(1, $field->_nbDigits('001'));
    }

    public function testExportPropertiesToXMLNoDefaultValue(): void
    {
        $file     = __DIR__ . '/../_fixtures/FieldDate/ImportTrackerFormElementDatePropertiesNoDefaultValueTest.xml';
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
        $date_field->shouldReceive('getProperties')->andReturn($properties);
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $date_field->exportPropertiesToXML($root);
        $this->assertEquals((string) $xml_test->properties, (string) $root->properties);
        $this->assertEquals(0, count($root->properties->attributes()));
    }

    public function testExportPropertiesToXMLNoDefaultValue2(): void
    {
        // another test if value = '0' instead of ''
        $file     = __DIR__ . '/../_fixtures/FieldDate/ImportTrackerFormElementDatePropertiesNoDefaultValueTest.xml';
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
        $date_field->shouldReceive('getProperties')->andReturn($properties);
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $date_field->exportPropertiesToXML($root);
        $this->assertEquals((string) $xml_test->properties, (string) $root->properties);
        $this->assertEquals(0, count($root->properties->attributes()));
    }

    public function testExportPropertiesToXMLDefaultValueToday(): void
    {
        $file     = __DIR__ . '/../_fixtures/FieldDate/ImportTrackerFormElementDatePropertiesDefaultValueTodayTest.xml';
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
        $date_field->shouldReceive('getProperties')->andReturn($properties);
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $date_field->exportPropertiesToXML($root);
        $this->assertEquals((string) $xml_test->properties, (string) $root->properties);
        $this->assertEquals(1, count($root->properties->attributes()));
        $attr = $root->properties->attributes();
        $this->assertEquals('today', ((string) $attr->default_value));
    }

    public function testExportPropertiesToXMLDefaultValueSpecificDate(): void
    {
        $file     = __DIR__ . '/../_fixtures/FieldDate/ImportTrackerFormElementDatePropertiesDefaultValueSpecificDateTest.xml';
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
        $date_field->shouldReceive('getProperties')->andReturn($properties);
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $date_field->exportPropertiesToXML($root);
        $this->assertEquals((string) $xml_test->properties, (string) $root->properties);
        $this->assertEquals(1, count($root->properties->attributes()));
        $attr = $root->properties->attributes();
        $this->assertEquals('1234567890', ((string) $attr->default_value));
    }

    public function testExportPropertiesToXMLDisplayTime(): void
    {
        $file     = __DIR__ . '/../_fixtures/FieldDate/ImportTrackerFormElementDatePropertiesDisplayTime.xml';
        $xml_test = simplexml_load_string(file_get_contents($file), SimpleXMLElement::class, LIBXML_NOENT);

        $date_field = $this->getDateField();
        $properties = [
            'display_time' => [
                'type'  => 'checkbox',
                'value' => 1,
            ],
        ];

        $date_field->shouldReceive('getProperties')->andReturn($properties);
        $date_field->shouldReceive('getProperty')->withArgs(['display_time'])->andReturn(1);

        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $date_field->exportPropertiesToXML($root);
        $this->assertEquals((string) $xml_test->properties, (string) $root->properties);
        $this->assertEquals(1, count($root->properties->attributes()));
        $attr = $root->properties->attributes();
        $this->assertEquals('1', ((string) $attr->display_time));
    }

    public function testExportPropertiesToXMLDisplayTimeWhenDisplayTimeIsZero(): void
    {
        $file     = __DIR__ . '/../_fixtures/FieldDate/ImportTrackerFormElementDatePropertiesDisplayTimeZero.xml';
        $xml_test = simplexml_load_string(file_get_contents($file), SimpleXMLElement::class, LIBXML_NOENT);

        $date_field = $this->getDateField();
        $properties = [
            'display_time' => [
                'type'  => 'checkbox',
                'value' => 0,
            ],
        ];

        $date_field->shouldReceive('getProperties')->andReturn($properties);
        $date_field->shouldReceive('getProperty')->withArgs(['display_time'])->andReturn(0);

        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $date_field->exportPropertiesToXML($root);
        $this->assertEquals((string) $xml_test->properties, (string) $root->properties);
        $this->assertEquals(1, count($root->properties->attributes()));
        $attr = $root->properties->attributes();
        $this->assertEquals('0', ((string) $attr->display_time));
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
        $date->shouldReceive('getProperty')->withArgs(['display_time'])->andReturn(0);
        $date->shouldReceive('getProperty')->withArgs(['default_value_type'])->andReturn(1);
        $date->shouldReceive('getProperty')->withArgs(['default_value'])->andReturn(1234564290);
        $date->shouldReceive('formatDate')->withArgs(['2009-02-13'])->andReturn(['1234564290']);

        $feedback_collector = \Mockery::mock(TrackerXmlImportFeedbackCollector::class);

        $date->continueGetInstanceFromXML($xml, $mapping, Mockery::mock(XMLImportHelper::class), $feedback_collector);

        $this->assertEquals('2009-02-13', $date->getDefaultValue());
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
        $date->shouldReceive('getProperty')->withArgs(['display_time'])->andReturn(0);
        $date->shouldReceive('getProperty')->withArgs(['default_value_type'])->andReturn(1);
        $date->shouldReceive('getProperty')->withArgs(['default_value'])->andReturn('date-of-today');
        $date->shouldReceive('formatDate')->andReturn('date-of-today');

        $feedback_collector = \Mockery::mock(TrackerXmlImportFeedbackCollector::class);

        $date->continueGetInstanceFromXML($xml, $mapping, Mockery::mock(XMLImportHelper::class), $feedback_collector);
        $this->assertEquals('date-of-today', $date->getDefaultValue());
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
        $date->shouldReceive('getProperty')->withArgs(['display_time'])->andReturn(0);
        $date->shouldReceive('getProperty')->withArgs(['default_value_type'])->andReturn(1);
        $date->shouldReceive('getProperty')->withArgs(['default_value'])->andReturn('');

        $feedback_collector = \Mockery::mock(TrackerXmlImportFeedbackCollector::class);

        $date->continueGetInstanceFromXML($xml, $mapping, Mockery::mock(XMLImportHelper::class), $feedback_collector);
        $this->assertEqualS('', $date->getDefaultValue());
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
        $date->shouldReceive('getProperty')->withArgs(['display_time'])->andReturn(0);
        $date->shouldReceive('getProperty')->withArgs(['default_value_type'])->andReturn(1);
        $date->shouldReceive('getProperty')->withArgs(['default_value'])->andReturn('');

        $feedback_collector = \Mockery::mock(TrackerXmlImportFeedbackCollector::class);

        $date->continueGetInstanceFromXML($xml, $mapping, Mockery::mock(XMLImportHelper::class), $feedback_collector);
        $this->assertEquals('', $date->getDefaultValue());
    }

    public function testFieldDateShouldSendEmptyMailValueWhenValueIsEmpty(): void
    {
        $user     = Mockery::mock(\PFUser::class);
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $date     = $this->getDateField();
        $this->assertEquals('-', $date->fetchMailArtifactValue($artifact, $user, false, null, null));
    }

    public function testFieldDateShouldSendAMailWithAReadableDateEnUS(): void
    {
        $GLOBALS['Language'] = Mockery::mock(BaseLanguage::class);
        $GLOBALS['Language']->shouldReceive('getText')->withArgs(['system', 'datefmt_short'])->andReturn('Y-m-d');
        $GLOBALS['Language']->shouldReceive('getLanguageFromAcceptLanguage')->andReturn('en_US');

        $user     = Mockery::mock(\PFUser::class);
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $date     = $this->getDateField();
        $date->shouldReceive('formatDateForDisplay')->with('2011-12-01')->andReturn(1322752769);
        $date->shouldReceive('isTimeDisplayed')->andReturnFalse();
        $date->shouldReceive('getArtifactTimeframeHelper')->andReturn(
            Mockery::mock(ArtifactTimeframeHelper::class, ['artifactHelpShouldBeShownToUser' => false])
        );

        $value = Mockery::mock(Tracker_Artifact_ChangesetValue_Date::class);
        $value->shouldReceive('getTimestamp')->andReturn(1322752769);

        $this->assertEquals('2011-12-01', $date->fetchMailArtifactValue($artifact, $user, false, $value, 'text'));
        $this->assertEquals('2011-12-01', $date->fetchMailArtifactValue($artifact, $user, false, $value, 'html'));
    }

    public function testFieldDateShouldSendAMailWithAReadableDatefrFR(): void
    {
        $GLOBALS['Language'] = Mockery::mock(BaseLanguage::class);
        $GLOBALS['Language']->shouldReceive('getText')->withArgs(['system', 'datefmt_short'])->andReturn('d/m/Y');
        $GLOBALS['Language']->shouldReceive('getLanguageFromAcceptLanguage')->andReturn('fr_FR');

        $user     = Mockery::mock(\PFUser::class);
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $date     = $this->getDateField();
        $date->shouldReceive('formatDateForDisplay')->with('2011-12-01')->andReturn(1322752769);
        $date->shouldReceive('isTimeDisplayed')->andReturnFalse();
        $date->shouldReceive('getArtifactTimeframeHelper')->andReturn(
            Mockery::mock(ArtifactTimeframeHelper::class, ['artifactHelpShouldBeShownToUser' => false])
        );

        $value = Mockery::mock(Tracker_Artifact_ChangesetValue_Date::class);
        $value->shouldReceive('getTimestamp')->andReturn(1322752769);

        $this->assertEquals('01/12/2011', $date->fetchMailArtifactValue($artifact, $user, false, $value, 'text'));
        $this->assertEquals('01/12/2011', $date->fetchMailArtifactValue($artifact, $user, false, $value, 'html'));
    }

    public function testFieldDateShouldSendEmptyMailWhenThereIsNoDateDefined(): void
    {
        $user     = Mockery::mock(\PFUser::class);
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $date     = $this->getDateField();

        $value = Mockery::mock(Tracker_Artifact_ChangesetValue_Date::class);
        $value->shouldReceive('getTimestamp')->andReturn(0);

        $this->assertEquals('-', $date->fetchMailArtifactValue($artifact, $user, false, $value, 'text'));
        $this->assertEquals('-', $date->fetchMailArtifactValue($artifact, $user, false, $value, 'html'));
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
        $reflection = new \ReflectionClass($field::class);
        $method     = $reflection->getMethod('getSQLCompareDate');
        $method->setAccessible(true);

        $field    = $this->getDateField();
        $fragment = $method->invokeArgs($field, [$is_advanced, "=", $from, $to, $column]);
        $this->assertEquals("my_date_column BETWEEN ? AND ?", $fragment->sql);
        $this->assertEquals([$from, $to + 86400 - 1], $fragment->parameters);
    }

    public function testItReturnsTheCorrectCriteriaForBeforeIncludingTheToDay(): void
    {
        $is_advanced = true;
        $column      = 'my_date_column';
        $to          = strtotime('2014-07-07');

        $field      = $this->getDateField();
        $reflection = new \ReflectionClass($field::class);
        $method     = $reflection->getMethod('getSQLCompareDate');
        $method->setAccessible(true);

        $fragment = $method->invokeArgs($field, [$is_advanced, "=", null, $to, $column]);
        $this->assertEquals("my_date_column <= ?", $fragment->sql);
        $this->assertEquals([$to + 86400 - 1], $fragment->parameters);
    }

    public function testItReturnsTheCorrectCriteriaForEquals(): void
    {
        $is_advanced = false;
        $column      = 'my_date_column';
        $from        = null;
        $to          = strtotime('2014-07-07');

        $field      = $this->getDateField();
        $reflection = new \ReflectionClass($field::class);
        $method     = $reflection->getMethod('getSQLCompareDate');
        $method->setAccessible(true);

        $fragment = $method->invokeArgs($field, [$is_advanced, "=", $from, $to, $column]);
        $this->assertEquals("my_date_column BETWEEN ? AND ?", $fragment->sql);
        $this->assertEquals([$to, $to + 86400 - 1], $fragment->parameters);
    }

    public function testItReturnsTheCorrectCriteriaForBefore(): void
    {
        $is_advanced = false;
        $column      = 'my_date_column';
        $from        = null;
        $to          = strtotime('2014-07-07');

        $field      = $this->getDateField();
        $reflection = new \ReflectionClass($field::class);
        $method     = $reflection->getMethod('getSQLCompareDate');
        $method->setAccessible(true);

        $fragment = $method->invokeArgs($field, [$is_advanced, "<", $from, $to, $column]);
        $this->assertEquals("my_date_column < ?", $fragment->sql);
        $this->assertEquals([$to], $fragment->parameters);
    }

    public function testItReturnsTheCorrectCriteriaForAfter(): void
    {
        $is_advanced = false;
        $column      = 'my_date_column';
        $from        = null;
        $to          = strtotime('2014-07-07');

        $field      = $this->getDateField();
        $reflection = new \ReflectionClass($field::class);
        $method     = $reflection->getMethod('getSQLCompareDate');
        $method->setAccessible(true);

        $fragment = $method->invokeArgs($field, [$is_advanced, ">", $from, $to, $column]);
        $this->assertEquals("my_date_column > ?", $fragment->sql);
        $this->assertEquals([$to + 86400 - 1], $fragment->parameters);
    }

    public function testItAddsAnEqualsCrterion(): void
    {
        $date                 = '2014-04-05';
        $criteria             = Mockery::mock(Tracker_Report_Criteria::class);
        $criteria->report     = Mockery::mock(Tracker_Report::class);
        $criteria->report->id = 1;
        $values               = [
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => $date,
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_EQUALS,
        ];

        $field = $this->getDateField();
        $field->setCriteriaValueFromREST($criteria, $values);
        $res = $field->getCriteriaValue($criteria);

        $this->assertCount(3, $res);
        $this->assertEquals('=', $res['op']);
        $this->assertEquals(0, $res['from_date']);
        $this->assertEquals(strtotime($date), $res['to_date']);
    }

    public function testItAddsAGreaterThanCrterion(): void
    {
        $date                 = '2014-04-05T00:00:00-05:00';
        $criteria             = Mockery::mock(Tracker_Report_Criteria::class);
        $criteria->report     = Mockery::mock(Tracker_Report::class);
        $criteria->report->id = 1;
        $values               = [
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => $date,
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_GREATER_THAN,
        ];

        $field = $this->getDateField();
        $field->setCriteriaValueFromREST($criteria, $values);
        $res = $field->getCriteriaValue($criteria);

        $this->assertCount(3, $res);
        $this->assertEquals('>', $res['op']);
        $this->assertEquals(0, $res['from_date']);
        $this->assertEquals(strtotime($date), $res['to_date']);
    }

    public function testItAddsALessThanCrterion(): void
    {
        $date                 = '2014-04-05';
        $criteria             = Mockery::mock(Tracker_Report_Criteria::class);
        $criteria->report     = Mockery::mock(Tracker_Report::class);
        $criteria->report->id = 1;
        $values               = [
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => [$date],
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_LESS_THAN,
        ];

        $field = $this->getDateField();
        $field->setCriteriaValueFromREST($criteria, $values);
        $res = $field->getCriteriaValue($criteria);

        $this->assertCount(3, $res);
        $this->assertEquals('<', $res['op']);
        $this->assertEquals(0, $res['from_date']);
        $this->assertEquals(strtotime($date), $res['to_date']);
    }

    public function testItAddsABetweenCrterion(): void
    {
        $from_date = '2014-04-05';
        $to_date   = '2014-05-12';
        $criteria  = Mockery::mock(Tracker_Report_Criteria::class);
        $criteria->shouldReceive('setIsAdvanced')->andReturn(false);
        $criteria->report     = Mockery::mock(Tracker_Report::class);
        $criteria->report->id = 1;
        $values               = [
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => [$from_date, $to_date],
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_BETWEEN,
        ];

        $field = $this->getDateField();
        $field->setCriteriaValueFromREST($criteria, $values);
        $res = $field->getCriteriaValue($criteria);

        $this->assertCount(3, $res);
        $this->assertEquals('=', $res['op']);
        $this->assertEquals(strtotime($from_date), $res['from_date']);
        $this->assertEquals(strtotime($to_date), $res['to_date']);
    }

    public function testItIgnoresInvalidDates(): void
    {
        $date = 'christmas eve';

        $criteria = Mockery::mock(Tracker_Report_Criteria::class);
        $values   = [
            Tracker_Report_REST::VALUE_PROPERTY_NAME    => $date,
            Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::OPERATOR_BETWEEN,
        ];

        $field = $this->getDateField();
        $res   = $field->setCriteriaValueFromREST($criteria, $values);
        $this->assertFalse($res);
    }
}
