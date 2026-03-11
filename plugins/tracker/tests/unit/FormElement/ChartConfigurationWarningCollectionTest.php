<?php
/**
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChartConfigurationWarningCollectionTest extends TestCase
{
    public function testGetAsHTML(): void
    {
        $collection = new ChartConfigurationWarningCollection();

        $collection->addWarning(ChartConfigurationWarning::fromMessage('The timeframe semantic is not defined.'));
        $collection->addWarning(
            ChartConfigurationWarningWithLinks::fromMessageAndLinks(
                'Configuration errors in the following locations:',
                new ChartConfigurationWarningLink('https://example.com', 'Here'),
                new ChartConfigurationWarningLink('https://example.com', 'There'),
            ),
        );

        self::assertEquals(
            '<ul class="feedback_warning">'
            . '<li>The timeframe semantic is not defined.</li>'
            . '<li>Configuration errors in the following locations: '
            . '<a href="https://example.com">Here</a>, '
            . '<a href="https://example.com">There</a>'
            . '</li></ul>',
            $collection->getAsHTML()
        );
    }
}
