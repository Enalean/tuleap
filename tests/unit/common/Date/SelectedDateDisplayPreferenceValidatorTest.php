<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Date;


class SelectedDateDisplayPreferenceValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var SelectedDateDisplayPreferenceValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new SelectedDateDisplayPreferenceValidator();
    }

    /**
     * @dataProvider dataProviderValidAndInvalidUserPreferences
     */
    public function testItValidatesTheSelectedUserPreference(
        string $new_relative_dates_display,
        bool $is_valid,
    ): void {
        $this->assertEquals(
            $is_valid,
            $this->validator->validateSelectedUserPreference($new_relative_dates_display)
        );
    }

    public static function dataProviderValidAndInvalidUserPreferences(): array
    {
        return [
            'PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP is valid' => [
                DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP,
                true,
            ],
            'PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP is valid' => [
                DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP,
                true,
            ],
            'PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN is valid' => [
                DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN,
                true,
            ],
            'PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN is valid' => [
                DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN,
                true,
            ],
            'yolo is not valid' => [
                'yolo',
                false,
            ],
        ];
    }
}
