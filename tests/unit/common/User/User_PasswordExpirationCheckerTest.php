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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class User_PasswordExpirationCheckerTest extends \PHPUnit\Framework\TestCase
{
    use \Tuleap\ForgeConfigSandbox;
    use \Tuleap\GlobalLanguageMock;

    private $password_expiration_checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->password_expiration_checker = new User_PasswordExpirationChecker();
    }

    public function testItRaisesAnExceptionWhenPasswordExpired(): void
    {
        ForgeConfig::set('sys_password_lifetime', 10);
        $this->expectException(\User_PasswordExpiredException::class);
        $this->password_expiration_checker->checkPasswordLifetime(
            new PFUser([
                'password'        => 'password',
                'status'          => PFUser::STATUS_ACTIVE,
                'last_pwd_update' => strtotime('15 days ago')
            ])
        );
    }
}
