<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\User\Password;

use ForgeConfig;
use Lcobucci\Clock\FrozenClock;
use PFUser;
use Psr\Clock\ClockInterface;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PasswordExpirationCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Tuleap\ForgeConfigSandbox;

    private ClockInterface $clock;
    private PasswordExpirationChecker $password_expiration_checker;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $date = \DateTimeImmutable::createFromFormat(\DateTimeImmutable::ATOM, '2025-09-17T00:00:00Z');
        assert($date instanceof \DateTimeImmutable);
        $this->clock                       = new FrozenClock($date);
        $this->password_expiration_checker = new PasswordExpirationChecker($this->clock);
    }

    public function testByDefaultPasswordsDoNotExpire(): void
    {
        $layout_inspector = new LayoutInspector();
        $layout           = LayoutBuilder::buildWithInspector($layout_inspector);

        $user = $this->buildUserWithPasswordUpdateDate($this->clock->now()->modify('15 years ago'));
        $this->password_expiration_checker->checkPasswordLifetime($user);
        $this->password_expiration_checker->warnUserAboutPasswordExpiration($layout, $user);

        $this->assertEmpty($layout_inspector->getFeedback());
    }

    public function testRaisesWarningWhenUserPasswordIsCloseToExpiration(): void
    {
        ForgeConfig::set('sys_password_lifetime', 10);

        $layout_inspector = new LayoutInspector();
        $layout           = LayoutBuilder::buildWithInspector($layout_inspector);

        $this->password_expiration_checker->warnUserAboutPasswordExpiration(
            $layout,
            $this->buildUserWithPasswordUpdateDate($this->clock->now()->modify('7 days ago'))
        );

        self::assertEquals(
            [
                [
                    'level' => \Feedback::WARN,
                    'message' => 'Your password will expire in 3 days.',
                ],
            ],
            $layout_inspector->getFeedback()
        );
    }

    public function testNoWarningAreRaisedWhenUserPasswordExpirationIsStillFarAway(): void
    {
        ForgeConfig::set('sys_password_lifetime', 90);

        $layout_inspector = new LayoutInspector();
        $layout           = LayoutBuilder::buildWithInspector($layout_inspector);

        $this->password_expiration_checker->warnUserAboutPasswordExpiration(
            $layout,
            $this->buildUserWithPasswordUpdateDate($this->clock->now()->modify('7 days ago'))
        );

        self::assertEmpty($layout_inspector->getFeedback());
    }

    public function testItRaisesAnExceptionWhenPasswordExpiredDueItsLifetime(): void
    {
        ForgeConfig::set('sys_password_lifetime', 10);
        $this->expectException(PasswordExpiredException::class);
        $this->password_expiration_checker->checkPasswordLifetime(
            $this->buildUserWithPasswordUpdateDate($this->clock->now()->modify('15 days ago'))
        );
    }

    public function testItRaisesAnExceptionWhenPasswordExpiredDueTheHardExpirationDate(): void
    {
        $current_date = $this->clock->now();
        ForgeConfig::set('sys_password_expiration_date', $current_date->modify('1 day ago')->format(\DateTimeImmutable::ATOM));
        $this->expectException(PasswordExpiredException::class);
        $this->password_expiration_checker->checkPasswordLifetime(
            $this->buildUserWithPasswordUpdateDate($current_date)
        );
    }

    public function testTakesTheShorterExpirationDateWhenCheckingPasswordExpiration(): void
    {
        $current_date = $this->clock->now();
        ForgeConfig::set('sys_password_expiration_date', $current_date->modify('1 day ago')->format(\DateTimeImmutable::ATOM));
        ForgeConfig::set('sys_password_lifetime', 2);

        $user = $this->buildUserWithPasswordUpdateDate($current_date);

        $this->expectException(PasswordExpiredException::class);
        $this->password_expiration_checker->checkPasswordLifetime($user);
    }

    private function buildUserWithPasswordUpdateDate(\DateTimeImmutable $last_password_update): PFUser
    {
        return new PFUser([
            'password' => 'password',
            'status' => PFUser::STATUS_ACTIVE,
            'last_pwd_update' => $last_password_update->getTimestamp(),
        ]);
    }
}
