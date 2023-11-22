<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

use Tuleap\ForgeConfigSandbox;

class DefaultRelativeDatesDisplayPreferenceRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testItRetrievesTheValueSetBySiteAdmin(): void
    {
        \ForgeConfig::set(
            DefaultRelativeDatesDisplayPreferenceRetriever::DEFAULT_RELATIVE_DATES_DISPLAY,
            DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN
        );

        $this->assertEquals(
            DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN,
            DefaultRelativeDatesDisplayPreferenceRetriever::retrieveDefaultValue()
        );
    }

    public function testItReturnsADefaultDisplayIfSiteAdminHasNotSetThePreference(): void
    {
        $this->assertEquals(
            DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP,
            DefaultRelativeDatesDisplayPreferenceRetriever::retrieveDefaultValue()
        );
    }

    /**
     * @var $preference_set_by_site_admin string|false
     * @dataProvider dataProviderPreferenceAndPlacement
     */
    public function testItReturnsADefaultRelativeDatesDisplayPreference(
        $preference_set_by_site_admin,
        string $position_from_context,
        string $expected_preference,
        string $expected_placement,
    ): void {
        \ForgeConfig::set(
            DefaultRelativeDatesDisplayPreferenceRetriever::DEFAULT_RELATIVE_DATES_DISPLAY,
            $preference_set_by_site_admin
        );

        $position_and_placement = DefaultRelativeDatesDisplayPreferenceRetriever::getDefaultPlacementAndPreference($position_from_context);

        $this->assertEquals($expected_preference, $position_and_placement->getPreference());
        $this->assertEquals($expected_placement, $position_and_placement->getPlacement());
    }

    public static function dataProviderPreferenceAndPlacement(): array
    {
        return [
            'absolute right' => [
                DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN,
                'right',
                'absolute',
                'right',
            ],
            'absolute top' => [
                DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN,
                'top',
                'absolute',
                'top',
            ],
            'absolute tooltip' => [
                DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP,
                'right',
                'absolute',
                'tooltip',
            ],
            'relative right' => [
                DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN,
                'right',
                'relative',
                'right',
            ],
            'relative top' => [
                DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN,
                'top',
                'relative',
                'top',
            ],
            'relative tooltip' => [
                DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP,
                'right',
                'relative',
                'tooltip',
            ],
            'default relative tooltip' => [
                false,
                'right',
                'relative',
                'tooltip',
            ],
        ];
    }
}
