<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Tracker\Creation;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use XML_ParseError;

class TrackerCreatorXmlErrorPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TrackerCreatorXmlErrorDisplayer
     */
    private $displayer;

    protected function setUp(): void
    {
        $this->displayer = new TrackerCreatorXmlErrorPresenterBuilder();
    }

    public function testItBuildAndCollectXmlError(): void
    {
        $error_invalid   = new XML_ParseError(1, 2, 'type', 'invalid line');
        $same_line_error = new XML_ParseError(1, 2, 'type', 'other error on same line');
        $error_required  = new XML_ParseError(10, 20, 'required', 'missing required property');
        $errors          = [
            $error_invalid,
            $same_line_error,
            $error_required
        ];

        $expected[1][2]   = [$error_invalid, $same_line_error];
        $expected[10][20] = [$error_required];

        $this->assertEquals($expected, $this->displayer->buildErrors($errors));
    }

    public function testItBuildAStringOfXmlLinesAndBreaksOnTheFirstErrorSeen(): void
    {
        $xml_file[] = '<project unix-name="taskboard" full-name="taskboard" access="public">';
        $xml_file[] = '<services>';
        $xml_file[] = '<service enabled="1"/>';
        $xml_file[] = '<service shortname="taskboard"/>';
        $xml_file[] = '<service shortname="mail" enabled="0"/>';
        $xml_file[] = '</services>';
        $xml_file[] = '</project>';

        $error       = new XML_ParseError(3, 12, 'type', 'missing shortname attributes');
        $other_error = new XML_ParseError(4, 12, 'type', 'missing enable attributes');

        $errors = $this->displayer->buildErrors([$error, $other_error]);

        $expected_lines[] = [
            'line_id'      => 1,
            'line_content' => '<project unix-name="taskboard" full-name="taskboard" access="public">',
            'has_error'    => false
        ];

        $expected_lines[] = [
            'line_id'      => 2,
            'line_content' => '<services>',
            'has_error'    => false
        ];

        $expected_lines[] = [
            'line_id'          => 3,
            'line_content'     => '<service enabled="1"/>',
            'has_error'        => true,
            'error_message'    => 'missing shortname attributes',
            'add_extra_spaces' => '              '
        ];

        $expected_lines[] = [
            'line_id'          => 4,
            'line_content'     => '<service shortname="taskboard"/>',
            'has_error'        => true,
            'error_message'    => 'missing enable attributes',
            'add_extra_spaces' => '              '
        ];

        $expected_lines[] = [
            'line_id'      => 5,
            'line_content' => '<service shortname="mail" enabled="0"/>',
            'has_error'    => false
        ];

        $expected_lines[] = [
            'line_id'      => 6,
            'line_content' => '</services>',
            'has_error'    => false
        ];

        $expected_lines[] = [
            'line_id'      => 7,
            'line_content' => '</project>',
            'has_error'    => false
        ];

        $expected_text = new TrackerCreatorXmlErrorPresenter($expected_lines);

        $built_string = $this->displayer->buildErrorLineDiff($xml_file, $errors);

        $this->assertEquals($expected_text, $built_string);
    }
}
