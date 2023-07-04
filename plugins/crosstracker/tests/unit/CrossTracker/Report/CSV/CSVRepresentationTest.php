<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\CSV;

final class CSVRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CSVRepresentation $representation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->representation = new CSVRepresentation();
    }

    public function testToStringWithComma(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('getPreference')->with('user_csv_separator')->willReturn('comma');
        $values = ['harrisite', 54, '03/04/2025 12:18'];

        $this->representation->build($values, $user);

        self::assertEquals($this->representation->__toString(), 'harrisite,54,03/04/2025 12:18');
    }

    public function testToStringWithSemicolon(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('getPreference')->with('user_csv_separator')->willReturn('semicolon');
        $values = [37, 'prethrill', '19/09/2031'];

        $this->representation->build($values, $user);

        self::assertEquals($this->representation->__toString(), '37;prethrill;19/09/2031');
    }

    public function testToStringWithTab(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('getPreference')->with('user_csv_separator')->willReturn('tab');
        $values = ['27/02/2018 10:10', '"Kara ""Starbuck"" Thrace"', 39.9749];

        $this->representation->build($values, $user);

        self::assertEquals(
            $this->representation->__toString(),
            "27/02/2018 10:10\t\"Kara \"\"Starbuck\"\" Thrace\"\t39.9749"
        );
    }
}
