<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

class SOAPRequestTest extends \PHPUnit\Framework\TestCase // phpcs:ignore
{
    public function testInit()
    {
        $request = new SOAPRequest([
           'param_1' => 'value_1',
           'param_2' => 'value_2']);
        $this->assertEquals('value_1', $request->get('param_1'));
        $this->assertEquals('value_2', $request->get('param_2'));
        $this->assertFalse($request->get('does_not_exist'));
    }
}
