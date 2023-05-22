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

use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;

final class BrowserDeprecationMessageTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testNoDeprecationMessageForModernBrowsers(): void
    {
        $detected_browser = $this->buildDetectedBrowserFromUserAgent('Some modern browser UA');

        self::assertNull(BrowserDeprecationMessage::fromDetectedBrowser(UserTestBuilder::aUser()->build(), $detected_browser));
    }

    public function testGetsDeprecationMessageForEdgeLegacy(): void
    {
        $detected_browser = $this->buildDetectedBrowserFromUserAgent(DetectedBrowserTest::EDGE_LEGACY_USER_AGENT_STRING);
        $message          = BrowserDeprecationMessage::fromDetectedBrowser(UserTestBuilder::aUser()->build(), $detected_browser);

        self::assertNotNull($message);
        self::assertTrue($message->can_be_dismiss);
    }

    public function testGetsDeprecationMessageForOutdatedVersionsOfSupportedBrowsers(): void
    {
        $detected_browser = $this->buildDetectedBrowserFromUserAgent(DetectedBrowserTest::VERY_OLD_FIREFOX_USER_AGENT_STRING);
        $message          = BrowserDeprecationMessage::fromDetectedBrowser(UserTestBuilder::aUser()->build(), $detected_browser);

        self::assertNotNull($message);
        self::assertTrue($message->can_be_dismiss);
    }

    public function testDeprecationMessageForOutdatedVersionsOfSupportedBrowsersCanBeDisabledWithASpecialForNonSiteAdminUsers(): void
    {
        \ForgeConfig::set('disable_old_browsers_warning', 'W21_I_understand_this_only_hides_the_message_for_non_siteadmin_users_and_that_issues_related_to_old_browsers_will_still_be_present');

        $detected_browser = $this->buildDetectedBrowserFromUserAgent(DetectedBrowserTest::VERY_OLD_FIREFOX_USER_AGENT_STRING);
        $user             = UserTestBuilder::aUser()->withoutSiteAdministrator()->build();
        $message          = BrowserDeprecationMessage::fromDetectedBrowser($user, $detected_browser);

        self::assertNull($message);
    }

    public function testDeprecationMessageForOutdatedVersionsOfSupportedBrowsersCannotBeDisabledWithASpecialForSiteAdminUsers(): void
    {
        \ForgeConfig::set('disable_old_browsers_warning', 'W21_I_understand_this_only_hides_the_message_for_non_siteadmin_users_and_that_issues_related_to_old_browsers_will_still_be_present');

        $detected_browser = $this->buildDetectedBrowserFromUserAgent(DetectedBrowserTest::VERY_OLD_FIREFOX_USER_AGENT_STRING);
        $user             = UserTestBuilder::buildSiteAdministrator();
        $message          = BrowserDeprecationMessage::fromDetectedBrowser($user, $detected_browser);

        self::assertNotNull($message);
        self::assertTrue($message->can_be_dismiss);
    }

    private function buildDetectedBrowserFromUserAgent(string $user_agent): DetectedBrowser
    {
        $request = $this->createStub(\HTTPRequest::class);
        $request->method('getFromServer')->with('HTTP_USER_AGENT')->willReturn($user_agent);

        return DetectedBrowser::detectFromTuleapHTTPRequest($request);
    }
}
