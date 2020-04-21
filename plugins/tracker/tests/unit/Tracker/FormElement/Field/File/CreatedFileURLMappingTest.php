<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\File;

use PHPUnit\Framework\TestCase;

class CreatedFileURLMappingTest extends TestCase
{
    public function testMapping()
    {
        $mapping = new CreatedFileURLMapping();

        $this->assertEquals(null, $mapping->get('/path/to/123'));
        $this->assertTrue($mapping->isEmpty());

        $mapping->add('/path/to/123', '/path/to/456');
        $this->assertEquals('/path/to/456', $mapping->get('/path/to/123'));
        $this->assertFalse($mapping->isEmpty());
    }
}
