<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once 'SomeClassWithNamespace.php';

class MockBuilder_WithNamespacesTest extends TuleapTestCase {

    public function itCanBuildTheMockIfThereIsANamespace() {
        $classname_to_mock = 'Tuleap\Test\SomeClassWithNamespace';

        $mock = mock($classname_to_mock);
        stub($mock)->someMethod()->returns("a precise result");

        $this->assertEqual(get_class($mock), 'MockTuleap_Test_SomeClassWithNamespace');
        $this->assertIsA($mock, $classname_to_mock);
        $this->assertEqual("a precise result", $mock->someMethod());
    }
}
