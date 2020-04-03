<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Test\Network;

use PHPUnit\Framework\TestCase;

final class HTTPResponseFunctionsOverloadTest extends TestCase
{
    public function tearDown(): void
    {
        HTTPHeaderStack::clear();
    }

    public function testOverloadedHTTPHeadersFunctions(): void
    {
        $this->assertFalse(\Tuleap\headers_sent());
        \Tuleap\header('Header: my_header');
        $this->assertFalse(\Tuleap\headers_sent());

        $stack = HTTPHeaderStack::getStack();

        $this->assertCount(1, $stack);
        $this->assertEquals('Header: my_header', $stack[0]->getHeader());
    }
}
