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
 *
 */

declare(strict_types=1);

namespace Tuleap;

use Tuleap\Layout\BaseLayout;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\Account\DisplaySecurityController;
use Tuleap\User\Account\UpdatePasswordController;
use Tuleap\User\CurrentUserWithLoggedInInformation;
use UserManager;

final class URLVerificationExpiredPasswordTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    /**
     * @var
     */
    private $url_verification;

    protected function setUp(): void
    {
        $fifteen_days_ago = new \DateTimeImmutable('15 days ago');
        $user             = UserTestBuilder::aUser()->withId(110)->withLastPwdUpdate((string) $fifteen_days_ago->getTimestamp())->build();
        $user_manager     = $this->createMock(UserManager::class);
        $user_manager->method('getCurrentUserWithLoggedInInformation')->willReturn(CurrentUserWithLoggedInInformation::fromLoggedInUser($user));
        UserManager::setInstance($user_manager);

        \ForgeConfig::set('sys_password_lifetime', '10');
        $GLOBALS['Response'] = $this->createMock(BaseLayout::class);

        $this->url_verification = new \URLVerification();
        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
        unset($GLOBALS['Response']);
    }

    public function testExpiredPasswordShouldRedirectToUpdatePasswordPage(): void
    {
        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(\Feedback::ERROR, 'Please update your password first');
        $GLOBALS['Response']->expects(self::once())->method('redirect')->with(DisplaySecurityController::URL);

        $this->url_verification->assertValidUrl(
            [
                'HTTPS'       => 'On',
                'SCRIPT_NAME' => 'index.php',
                'REQUEST_URI' => '/my',
            ],
            HTTPRequestBuilder::get()->build()
        );
    }

    public function testExpiredPasswordShouldAllowToBrowseChangePasswordPage(): void
    {
        $this->expectNotToPerformAssertions();
        $this->url_verification->assertValidUrl(
            [
                'HTTPS'       => 'On',
                'SCRIPT_NAME' => 'index.php',
                'REQUEST_URI' => DisplaySecurityController::URL,
            ],
            HTTPRequestBuilder::get()->build()
        );
    }

    public function testExpiredPasswordShouldAllowToAccessUpdatePasswordPage(): void
    {
        $this->expectNotToPerformAssertions();
        $this->url_verification->assertValidUrl(
            [
                'HTTPS'       => 'On',
                'SCRIPT_NAME' => 'index.php',
                'REQUEST_URI' => UpdatePasswordController::URL,
            ],
            HTTPRequestBuilder::get()->build()
        );
    }
}
