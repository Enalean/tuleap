<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class RandomNumberGeneratorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItGeneratesTokenOfTheAskedSize(): void
    {
        $number_generator_8_bits = new RandomNumberGenerator(1);
        self::assertEquals(2, strlen($number_generator_8_bits->getNumber()));
        $number_generator_64_bits = new RandomNumberGenerator(8);
        self::assertEquals(16, strlen($number_generator_64_bits->getNumber()));
        $number_generator_128_bits = new RandomNumberGenerator();
        self::assertEquals(32, strlen($number_generator_128_bits->getNumber()));
    }
}
