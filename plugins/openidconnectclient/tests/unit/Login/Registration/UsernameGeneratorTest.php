<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Login\Registration;

use Cocur\Slugify\Slugify;
use Tuleap\User\DataIncompatibleWithUsernameGenerationException;
use Tuleap\User\UserNameNormalizer;

require_once(__DIR__ . '/../../bootstrap.php');

final class UsernameGeneratorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private UserNameNormalizer&\PHPUnit\Framework\MockObject\MockObject $userNameNormalizer;

    protected function setUp(): void
    {
        $this->userNameNormalizer = $this->createMock(UserNameNormalizer::class);
    }

    public function testItGeneratesUsernameFromPreferredUsername(): void
    {
        $rule = $this->createMock(\Rule_UserName::class);
        $rule->method('isUnixValid')->willReturn(true);
        $rule->method('isValid')->willReturn(true);

        $username_generator = new UsernameGenerator($this->userNameNormalizer);

        $username = "mypreferredusername";
        $this->userNameNormalizer->method("normalize")->with("mypreferredusername")->willReturn(
            $username
        );

        $generated_username = $username_generator->getUsername(
            [
                'preferred_username' => $username,
            ]
        );

        self::assertEquals($username, $generated_username);
    }

    public function testItGeneratesUsernameFromGivenAndFamilyNames(): void
    {
        $rule = $this->createMock(\Rule_UserName::class);
        $rule->method('isUnixValid')->willReturn(true);
        $rule->method('isValid')->willReturn(true);
        $username_generator = new UsernameGenerator($this->userNameNormalizer);

        $username = "gfamilyname";

        $this->userNameNormalizer->method("normalize")->with($username)->willReturn(
            $username
        );

        $generated_username = $username_generator->getUsername(
            [
                'given_name'  => 'Given Name',
                'family_name' => 'Family Name',
            ]
        );
        self::assertEquals($username, $generated_username);
    }

    public function testItGeneratesUsernameFromFamilyName(): void
    {
        $rule = $this->createMock(\Rule_UserName::class);
        $rule->method('isUnixValid')->willReturn(true);
        $rule->method('isValid')->willReturn(true);
        $username_generator = new UsernameGenerator($this->userNameNormalizer);

        $username = "familyname";

        $this->userNameNormalizer->method("normalize")->with("familyname")->willReturn(
            $username
        );

        $generated_username = $username_generator->getUsername(
            [
                'family_name' => 'Family Name',
            ]
        );
        self::assertEquals($username, $generated_username);
    }

    public function testItGeneratesUsernameFromGivenName(): void
    {
        $rule = $this->createMock(\Rule_UserName::class);
        $rule->method('isUnixValid')->willReturn(true);
        $rule->method('isValid')->willReturn(true);
        $username_generator = new UsernameGenerator($this->userNameNormalizer);

        $username = "givenname";

        $this->userNameNormalizer->method("normalize")->with($username)->willReturn(
            $username
        );
        $generated_username = $username_generator->getUsername(
            [
                'given_name' => 'Given Name',
            ]
        );
        self::assertEquals($username, $generated_username);
    }

    public function testItNeedsAtLeastGivenOrFamilyNamesToGenerateUsername(): void
    {
        $rule = $this->createMock(\Rule_UserName::class);
        $rule->method('isUnixValid')->willReturn(true);
        $rule->method('isValid')->willReturn(true);
        $username_generator = new UsernameGenerator($this->userNameNormalizer);

        $this->userNameNormalizer->expects(self::never())->method("normalize");
        $this->expectException(NotEnoughDataToGenerateUsernameException::class);
        $username_generator->getUsername([]);
    }

    public function testItNeedsDataCompatibleWithUnixUsername(): void
    {
        $rule = $this->createMock(\Rule_UserName::class);
        $rule->method('isUnixValid')->willReturn(false);
        $rule->method('isValid')->willReturn(true);
        $username_generator = new UsernameGenerator($this->userNameNormalizer);

        $this->userNameNormalizer->method("normalize")->willThrowException(new DataIncompatibleWithUsernameGenerationException());
        $this->expectException(
            DataIncompatibleWithUsernameGenerationException::class
        );
        $username_generator->getUsername(
            [
                'given_name'  => 'IncompatibleGivenName',
                'family_name' => 'IncompatibleFamilyName',
            ]
        );
    }

    public function testItTriesToUseGivenAndFamilyNamesEvenIfPreferredUsernameIsNotCompatible(): void
    {
        $rule = $this->createMock(\Rule_UserName::class);
        $rule->method('isUnixValid')->willReturn(false, true);
        $rule->method('isValid')->willReturn(true);

        $rulename = $this->createMock(\Rule_UserName::class);
        $rulename->method('isUnixValid')->willReturnCallback(static fn (string $name): bool => match ($name) {
            'incompatiblepreferredusername' => false,
            'gfamilyname' => true,
        });
        $rulename->method('isValid')->willReturnCallback(static fn (string $name): bool => match ($name) {
            'gfamilyname' => true,
        });
        $username_generator = new UsernameGenerator(new UserNameNormalizer($rulename, new Slugify()));

        $username = "gfamilyname";

        $generated_username = $username_generator->getUsername(
            [
                'preferred_username' => 'incompatiblepreferredusername',
                'given_name'         => 'Given Name',
                'family_name'        => 'Family Name',
            ]
        );
        self::assertEquals($username, $generated_username);
    }
}
