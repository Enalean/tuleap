<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2011. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * This is an example of mocking functions in simple test.
 * To run this test all you have to do after installing runkit is
 * to copy this file with other tests, then run it.
 */

function foobar() {
    return 'foo';
}

class MockFunctionsTest extends TuleapTestCase {

    function testFoobar() {
        $this->skipUnless(MOCKFUNCTION_AVAILABLE, "Function mocking not available");
        if (MOCKFUNCTION_AVAILABLE) {
            MockFunction::generate('foobar');

            MockFunction::setReturnValue('foobar','bar');
            MockFunction::expectCallCount('foobar',2);

            $this->assertEqual(foobar(), 'bar');
            $this->assertEqual(foobar(), 'bar');

            MockFunction::restore('foobar');
        }
    }
}

?>