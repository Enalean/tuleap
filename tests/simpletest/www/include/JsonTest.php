<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
require_once('www/include/json.php');

class JsonTest extends TuleapTestCase {
    
    public function testStartsWithHeaderInfo() {
        $this->assertPattern("/^X-JSON:.*/", json_header('something'));
    }
    public function testJsonFormat() {
        $this->assertThat('toto')->encodesTo('"toto"');
        $this->assertThat('with { ( [ )->encodesTo(simple\' quote and double " quote')->encodesTo('"with { ( [ )->encodesTo(simple\' quote and double \\" quote"');
        $this->assertThat(null)->encodesTo('null');
        $this->assertThat(123)->encodesTo('123');
        $this->assertThat(' ')->encodesTo('" "');
    }

    private function assertThat($message) {
        return new Asserter($this, $message);
    }
    
}

class Asserter {
    protected $testcase;
    protected $input;

    public function __construct($testcase, $input) {
        $this->input = $input;
        $this->testcase = $testcase;
    }

    public function encodesTo($expected) {
        $this->testcase->assertEqual('X-JSON: {"whatever":false,"msg":'.$expected.'}', json_header(array("whatever" => false, "msg" => $this->input)));
    }
}
?>
