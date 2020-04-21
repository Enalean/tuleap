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

namespace Tuleap\Git;

use PHPUnit\Framework\TestCase;

final class BinaryDetectorTest extends TestCase
{
    /**
     * @testWith ["Tuleap"]
     *           ["<?xml version=\"1.1\"?><_/>"]
     */
    public function testTextDataIsNotRecognizedAsBinary(string $text): void
    {
        $this->assertFalse(BinaryDetector::isBinary($text));
    }

    /**
     * @testWith ["jpeg"]
     *           ["png"]
     *           ["tar"]
     *           ["zip"]
     */
    public function testBinaryContentIsRecognizedAsBinary(string $fixture_name): void
    {
        $this->assertTrue(BinaryDetector::isBinary(file_get_contents(__DIR__ . '/fixtures/' . $fixture_name)));
    }
}
