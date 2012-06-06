<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class TuleapTestCaseTest extends TuleapTestCase {
    
    public function testAssertUriHasArgument() {
        $this->assertTrue($this->assertUriHasArgument('/foo?bar=baz_baz', 'bar', 'baz_baz'));
        $this->assertFalse($this->assertUriHasArgument('/foo?bar=baz_baz', 'bar', 'baz'));
        $this->assertFalse($this->assertUriHasArgument('/foo?bar=baz', 'bar', 'b.z'));
        $this->assertTrue($this->assertUriHasArgument('/foo?bar=b/z', 'bar', 'b/z'));
        $this->assertTrue($this->assertUriHasArgument('/foo?bar=baz&truc=muche', 'bar', 'baz'));
    }
    
    public function assertUriHasArgument($uri, $param, $value) {
        //disable the current reporter when we test assertions
        $reporter        = $this->_reporter;
        $this->_reporter = new SimpleReporter();
        
        //assert
        $result = parent::assertUriHasArgument($uri, $param, $value);
        
        //restore the old reporter
        $this->_reporter = $reporter;
        
        return $result;
    }
}
?>
