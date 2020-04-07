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

final class HTTPHeaderStackTest extends TestCase
{
    public function tearDown(): void
    {
        HTTPHeaderStack::clear();
    }

    public function testHeadersAreAddedToTheStack(): void
    {
        HTTPHeaderStack::push(new HTTPHeader('test1', false, null));
        HTTPHeaderStack::push(new HTTPHeader('test2', false, null));

        $stack = HTTPHeaderStack::getStack();

        $this->assertCount(2, $stack);
        $this->assertEquals('test1', $stack[0]->getHeader());
        $this->assertEquals('test2', $stack[1]->getHeader());
    }

    public function testHeaderStackCanBeCleared(): void
    {
        $this->assertEmpty(HTTPHeaderStack::getStack());

        HTTPHeaderStack::push(new HTTPHeader('test', false, null));
        HTTPHeaderStack::clear();

        $this->assertEmpty(HTTPHeaderStack::getStack());
    }
}
