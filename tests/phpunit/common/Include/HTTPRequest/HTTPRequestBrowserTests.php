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

class HTTPRequestBrowserTests extends \PHPUnit\Framework\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, \Tuleap\GlobalLanguageMock, \Tuleap\ForgeConfigSandbox, \Tuleap\TemporaryTestDirectory;

    /** @var HTTPRequest */
    private $request;

    /** @var PFUser */
    private $user;

    private $msg_ie_deprecated        = 'ie warning message';
    private $msg_ie_deprecated_button = 'disable ie warning';

    protected function setUp(): void
    {
        $GLOBALS['Language']->shouldReceive('getText')->with(Mockery::any(), 'ie_deprecated')->andReturns($this->msg_ie_deprecated);
        $GLOBALS['Language']->shouldReceive('getText')->with(Mockery::any(), 'ie_deprecated_button')->andReturns($this->msg_ie_deprecated_button);

        $this->user   = \Mockery::spy(\PFUser::class);
        $user_manager = \Mockery::spy(\UserManager::class)->shouldReceive('getCurrentUser')->andReturns($this->user)->getMock();
        UserManager::setInstance($user_manager);

        $this->request = new HTTPRequest();
        $this->request->setCurrentUser($this->user);

        ForgeConfig::set('codendi_dir', __DIR__ . '/../../../../../');
        ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
        unset($_SERVER['HTTP_USER_AGENT']);
        unset($_SESSION);
    }

    public function testNoNoticesWhenNoUserAgent()
    {
        $this->doesNotPerformAssertions();
        unset($_SERVER['HTTP_USER_AGENT']);
        $this->request->getBrowser();
    }

    public function testIE9CompatibilityModeIsDeprected()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)';
        $browser = $this->request->getBrowser();

        $this->assertStringContainsString('ie warning message', $browser->getDeprecatedMessage());
    }

    public function testIE10CompatibilityModeIsDeprected()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/6.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C; .NET4.0E)';
        $browser = $this->request->getBrowser();

        $this->assertStringContainsString('ie warning message', $browser->getDeprecatedMessage());
    }

    public function testIE11CompatibilityModeIsDeprected()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; Trident/7.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)';
        $browser = $this->request->getBrowser();

        $this->assertStringContainsString('ie warning message', $browser->getDeprecatedMessage());
    }

    public function testIE9IsDeprecated()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)';
        $browser = $this->request->getBrowser();

        $this->assertStringContainsString('ie warning message', $browser->getDeprecatedMessage());
    }

    public function testFirefoxIsNotDeprecated()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:27.0) Gecko/20100101 Firefox/27.0';
        $browser = $this->request->getBrowser();

        $GLOBALS['Language']->shouldReceive('getText')->never();

        $browser->getDeprecatedMessage();
    }

    public function testIE8IsDeprecated()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; GTB7.4; InfoPath.2; SV1; .NET CLR 3.3.69573; WOW64; en-US)';
        $browser = $this->request->getBrowser();

        $this->assertStringContainsString('ie warning message', $browser->getDeprecatedMessage());
    }

    public function testIE7IsDeprecated()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.0)';
        $browser = $this->request->getBrowser();

        $this->assertStringContainsString('ie warning message', $browser->getDeprecatedMessage());
    }

    public function testIE7IsDeprecatedButUserChoseToNotDisplayTheWarning()
    {
        $this->user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_DISABLE_IE7_WARNING)->andReturns(1);

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.0)';
        $browser = $this->request->getBrowser();

        $this->assertStringNotContainsString('ie warning message', $browser->getDeprecatedMessage());
    }

    public function testItDisplaysOkButtonToDisableIE7Warning()
    {
        $this->user->shouldReceive('isAnonymous')->andReturns(false);

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.0)';
        $browser = $this->request->getBrowser();

        $this->assertStringContainsString('disable ie warning', $browser->getDeprecatedMessage());
    }

    public function testItDoesNotDisplayOkButtonForAnonymousUser()
    {
        $this->user->shouldReceive('isAnonymous')->andReturns(true);

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.0)';
        $browser = $this->request->getBrowser();

        $this->assertStringNotContainsString('disable ie warning', $browser->getDeprecatedMessage());
    }

    public function testItReturnsTrueIfBrowserIsIE11()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; Trident/7.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)';
        $browser = $this->request->getBrowser();

        $this->assertTrue($browser->isIE11());
    }

    public function testItReturnsFalseIfBrowserIsNotIE11()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:27.0) Gecko/20100101 Firefox/27.0';
        $browser = $this->request->getBrowser();

        $this->assertFalse($browser->isIE11());
    }
}
