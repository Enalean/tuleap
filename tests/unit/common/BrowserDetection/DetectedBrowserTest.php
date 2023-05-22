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

final class DetectedBrowserTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public const CHROME_USER_AGENT_STRING           = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.134 Safari/537.36';
    public const FIREFOX_USER_AGENT_STRING          = 'Mozilla/5.0 (X11; Linux x86_64; rv:102.0) Gecko/20100101 Firefox/102.0';
    public const IE11_USER_AGENT_STRING             = 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko';
    private const OLD_IE_USER_AGENT_STRING          = 'Mozilla/4.0 (compatible; MSIE 4.01; Mac_PowerPC)';
    public const EDGE_LEGACY_USER_AGENT_STRING      = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36 Edge/18.17763';
    public const VERY_OLD_FIREFOX_USER_AGENT_STRING = 'Mozilla/5.0 (Windows NT 6.1; rv:68.7) Gecko/20100101 Firefox/68.7';

    /**
     * @dataProvider dataProviderBrowserUA
     */
    public function testDetectsBrowser(
        string $user_agent,
        ?string $expected_browser_name,
        bool $expected_is_ie,
        bool $expected_edge_legacy,
        bool $expected_browser_is_outdated,
        bool $expected_browser_is_completely_broken,
    ): void {
        $detected_browser = $this->buildDetectedBrowserFromSpecificUserAgentString($user_agent);
        self::assertEquals($expected_browser_name, $detected_browser->getName());
        self::assertEquals($expected_is_ie, $detected_browser->isIE());
        self::assertEquals($expected_edge_legacy, $detected_browser->isEdgeLegacy());
        self::assertEquals($expected_browser_is_outdated, $detected_browser->isAnOutdatedBrowser());
        self::assertEquals($expected_browser_is_completely_broken, $detected_browser->isACompletelyBrokenBrowser());
    }

    public static function dataProviderBrowserUA(): array
    {
        return [
            'IE11' => [
                self::IE11_USER_AGENT_STRING,
                'Internet Explorer',
                true,
                false,
                true,
                true,
            ],
            'Old IE' => [
                self::OLD_IE_USER_AGENT_STRING,
                'Internet Explorer',
                true,
                false,
                true,
                true,
            ],
            'Edge Legacy' => [
                self::EDGE_LEGACY_USER_AGENT_STRING,
                'Edge Legacy',
                false,
                true,
                true,
                false,
            ],
            'Edge' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3738.0 Safari/537.36 Edg/75.0.107.0',
                'Edge',
                false,
                false,
                false,
                false,
            ],
            'Firefox' => [
                self::FIREFOX_USER_AGENT_STRING,
                'Firefox',
                false,
                false,
                false,
                false,
            ],
            'Very Old Firefox' => [
                self::VERY_OLD_FIREFOX_USER_AGENT_STRING,
                'Firefox',
                false,
                false,
                true,
                false,
            ],
            'Chrome' => [
                self::CHROME_USER_AGENT_STRING,
                'Chrome',
                false,
                false,
                false,
                false,
            ],
            'Very Old Chrome' => [
                'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36',
                'Chrome',
                false,
                false,
                true,
                false,
            ],
            'Chromium' => [
                'Mozilla/5.0 (X11; U; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/103.0.4988.153 Chrome/103.0.4988.153 Safari/537.36',
                'Chrome',
                false,
                false,
                false,
                false,
            ],
            'curl' => [
                'curl/7.71.1',
                null,
                false,
                false,
                false,
                false,
            ],
        ];
    }

    public function testDoesNotIdentifyAnythingWhenNoUserAgentHeaderIsSet(): void
    {
        $request = $this->createStub(\HTTPRequest::class);
        $request->method('getFromServer')->with('HTTP_USER_AGENT')->willReturn(false);

        $detected_browser = DetectedBrowser::detectFromTuleapHTTPRequest($request);

        self::assertNull($detected_browser->getName());
        self::assertFalse($detected_browser->isIE());
        self::assertFalse($detected_browser->isEdgeLegacy());
        self::assertFalse($detected_browser->isAnOutdatedBrowser());
        self::assertFalse($detected_browser->isACompletelyBrokenBrowser());
    }

    private function buildDetectedBrowserFromSpecificUserAgentString(string $user_agent): DetectedBrowser
    {
        $request = $this->createStub(\HTTPRequest::class);
        $request->method('getFromServer')->with('HTTP_USER_AGENT')->willReturn($user_agent);

        return DetectedBrowser::detectFromTuleapHTTPRequest($request);
    }
}
