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

use Codendi_HTMLPurifier;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use TrackerManager;
use XML_ParseError;

class TrackerCreatorXmlErrorDisplayerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var Codendi_HTMLPurifier|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $purifier;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerManager
     */
    private $tracker_manager;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerCreatorXmlErrorDisplayer
     */
    private $displayer;

    protected function setUp(): void
    {
        $this->tracker_manager = Mockery::mock(TrackerManager::class);
        $this->purifier        = Codendi_HTMLPurifier::instance();

        $this->displayer = Mockery::mock(TrackerCreatorXmlErrorDisplayer::class, [$this->tracker_manager, $this->purifier])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
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

        $expected [1][2]   = [$error_invalid, $same_line_error];
        $expected [10][20] = [$error_required];

        $this->assertEquals($expected, $this->displayer->buildErrors($errors));
    }

    public function testItBuildAStringOfXmlLinesAndBreaksOnTheFirstErrorSeen(): void
    {
        $xml_file[] = '<project unix-name="taskboard" full-name="taskboard" access="public">';
        $xml_file[] = '<services>';
        $xml_file[] = '<service enabled="1"/>';
        $xml_file[] = '<service shortname="mail" enabled="0"/>';
        $xml_file[] = '</services>';
        $xml_file[] = '</project>';

        $error = new XML_ParseError(3, 12, 'type', 'missing shortname attributes');

        $errors = $this->displayer->buildErrors([$error]);

        $line_one = '<div id="line_1">';
        $line_one .= '<span style="color:gray;">   1</span>';
        $line_one .= '&lt;project unix-name=&quot;taskboard&quot; full-name=&quot;taskboard&quot; access=&quot;public&quot;&gt;';
        $line_one .= '</div>';

        $line_two = '<div id="line_2">';
        $line_two .= '<span style="color:gray;">   2</span>';
        $line_two .= '&lt;services&gt;';
        $line_two .= '</div>';

        $line_three = '<div id="line_3">';
        $line_three .= '<span style="color:gray;">   3</span>';
        $line_three .= '&lt;service enabled=&quot;1&quot;/&gt;';
        $line_three .= '<div>              <span style="color:blue; font-weight:bold;">^</span></div><div style="color:red; font-weight:bold;">              missing shortname attributes</div>';
        $line_three .= '</div>';


        $line_four = '<div id="line_4">';
        $line_four .= '<span style="color:gray;">   4</span>';
        $line_four .= '&lt;service shortname=&quot;mail&quot; enabled=&quot;0&quot;/&gt;';
        $line_four .= '</div>';

        $line_five = '<div id="line_5">';
        $line_five .= '<span style="color:gray;">   5</span>';
        $line_five .= '&lt;/services&gt;';
        $line_five .= '</div>';

        $line_six = '<div id="line_6">';
        $line_six .= '<span style="color:gray;">   6</span>';
        $line_six .= '&lt;/project&gt;';
        $line_six .= '</div>';

        $expected_text = '<pre>'. $line_one . $line_two . $line_three . $line_four . $line_five . $line_six . '</pre>';

        $this->displayer->shouldReceive('retrieveClearImage');
        $this->displayer->shouldReceive('retrieverErrorIcon');

        $built_string = $this->displayer->buildErrorLineDiff($xml_file, $errors);

        $this->assertEquals($expected_text, $built_string);
    }
}
