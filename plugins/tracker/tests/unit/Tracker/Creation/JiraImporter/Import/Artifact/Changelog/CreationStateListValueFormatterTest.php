<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog;

use PHPUnit\Framework\TestCase;

class CreationStateListValueFormatterTest extends TestCase
{
    /**
     * @var CreationStateListValueFormatter
     */
    private $formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter = new CreationStateListValueFormatter();
    }

    public function testItFormatsSimpleListValue(): void
    {
        $value = "10000";

        $formatted_value = $this->formatter->formatListValue($value);

        $this->assertSame(['id' => "10000"], $formatted_value);
    }

    public function testItFormatsMultipleListValue(): void
    {
        $value = "[10000, 10001]";

        $formatted_value = $this->formatter->formatListValue($value);

        $this->assertSame(
            [
                ['id' => "10000"],
                ['id' => "10001"],
            ],
            $formatted_value
        );
    }

    public function testItFormatsMultiUserListValues(): void
    {
        $value = [105, 106, 201];

        $formatted_value = $this->formatter->formatMultiUserListValues($value);

        $this->assertSame(
            [
                ['id' => "105"],
                ['id' => "106"],
                ['id' => "201"],
            ],
            $formatted_value
        );
    }
}
