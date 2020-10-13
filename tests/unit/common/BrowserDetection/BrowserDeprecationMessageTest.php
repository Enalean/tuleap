<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\BrowserDetection;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class BrowserDeprecationMessageTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testNoDeprecationMessageForModernBrowsers(): void
    {
        $detected_browser = $this->buildDetectedBrowserFromUserAgent('Some modern browser UA');

        self::assertNull(BrowserDeprecationMessage::fromDetectedBrowser($detected_browser));
    }

    public function testGetsDeprecationMessageForIE11(): void
    {
        $detected_browser = $this->buildDetectedBrowserFromUserAgent(DetectedBrowserTest::IE11_USER_AGENT_STRING);

        self::assertNotNull($detected_browser);
    }

    public function testGetsDeprecationMessageForOldIE(): void
    {
        $detected_browser = $this->buildDetectedBrowserFromUserAgent(DetectedBrowserTest::OLD_IE_USER_AGENT_STRING);

        self::assertNotNull($detected_browser);
    }

    public function testGetsDeprecationMessageForEdgeLegacy(): void
    {
        $detected_browser = $this->buildDetectedBrowserFromUserAgent(DetectedBrowserTest::EDGE_LEGACY_USER_AGENT_STRING);

        self::assertNotNull($detected_browser);
    }

    private function buildDetectedBrowserFromUserAgent(string $user_agent): DetectedBrowser
    {
        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getFromServer')->with('HTTP_USER_AGENT')->andReturn($user_agent);

        return DetectedBrowser::detectFromTuleapHTTPRequest($request);
    }
}
