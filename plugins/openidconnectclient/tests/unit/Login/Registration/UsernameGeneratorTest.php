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

namespace Tuleap\OpenIDConnectClient\Login\Registration;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\User\DataIncompatibleWithUsernameGenerationException;
use Tuleap\User\UserNameNormalizer;

require_once(__DIR__ . '/../../bootstrap.php');

class UsernameGeneratorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private UserNameNormalizer $userNameNormalizer;

    protected function setUp(): void
    {
        $this->userNameNormalizer = \Mockery::mock(UserNameNormalizer::class);
    }

    public function testItGeneratesUsernameFromPreferredUsername(): void
    {
        $rule = \Mockery::spy(\Rule_UserName::class);
        $rule->shouldReceive('isUnixValid')->andReturns(true);
        $rule->shouldReceive('isValid')->andReturns(true);

        $username_generator = new UsernameGenerator($this->userNameNormalizer);

        $username = "mypreferredusername";
        $this->userNameNormalizer->shouldReceive("normalize")->withArgs(["mypreferredusername"])->andReturn(
            $username
        );

        $generated_username = $username_generator->getUsername(
            [
                'preferred_username' => $username,
            ]
        );

        $this->assertEquals($username, $generated_username);
    }

    public function testItGeneratesUsernameFromGivenAndFamilyNames(): void
    {
        $rule = \Mockery::spy(\Rule_UserName::class);
        $rule->shouldReceive('isUnixValid')->andReturns(true);
        $rule->shouldReceive('isValid')->andReturns(true);
        $username_generator = new UsernameGenerator($this->userNameNormalizer);

        $username = "gfamilyname";

        $this->userNameNormalizer->shouldReceive("normalize")->withArgs([$username])->andReturn(
            $username
        );

        $generated_username = $username_generator->getUsername(
            [
                'given_name'  => 'Given Name',
                'family_name' => 'Family Name',
            ]
        );
        $this->assertEquals($username, $generated_username);
    }

    public function testItGeneratesUsernameFromFamilyName(): void
    {
        $rule = \Mockery::spy(\Rule_UserName::class);
        $rule->shouldReceive('isUnixValid')->andReturns(true);
        $rule->shouldReceive('isValid')->andReturns(true);
        $username_generator = new UsernameGenerator($this->userNameNormalizer);

        $username = "familyname";

        $this->userNameNormalizer->shouldReceive("normalize")->withArgs(["familyname"])->andReturn(
            $username
        );

        $generated_username = $username_generator->getUsername(
            [
                'family_name' => 'Family Name',
            ]
        );
        $this->assertEquals($username, $generated_username);
    }

    public function testItGeneratesUsernameFromGivenName(): void
    {
        $rule = \Mockery::spy(\Rule_UserName::class);
        $rule->shouldReceive('isUnixValid')->andReturns(true);
        $rule->shouldReceive('isValid')->andReturns(true);
        $username_generator = new UsernameGenerator($this->userNameNormalizer);

        $username = "givenname";

        $this->userNameNormalizer->shouldReceive("normalize")->withArgs([$username])->andReturn(
            $username
        );
        $generated_username = $username_generator->getUsername(
            [
                'given_name' => 'Given Name',
            ]
        );
        $this->assertEquals($username, $generated_username);
    }


    public function testItNeedsAtLeastGivenOrFamilyNamesToGenerateUsername(): void
    {
        $rule = \Mockery::spy(\Rule_UserName::class);
        $rule->shouldReceive('isUnixValid')->andReturns(true);
        $rule->shouldReceive('isValid')->andReturns(true);
        $username_generator = new UsernameGenerator($this->userNameNormalizer);

        $this->userNameNormalizer->shouldReceive("normalize")->never();
        $this->expectException(
            'Tuleap\OpenIDConnectClient\Login\Registration\NotEnoughDataToGenerateUsernameException'
        );
        $username_generator->getUsername([]);
    }

    public function testItNeedsDataCompatibleWithUnixUsername(): void
    {
        $rule = \Mockery::spy(\Rule_UserName::class);
        $rule->shouldReceive('isUnixValid')->andReturns(false);
        $rule->shouldReceive('isValid')->andReturns(true);
        $username_generator = new UsernameGenerator($this->userNameNormalizer);

        $this->userNameNormalizer->shouldReceive("normalize")->andThrow(
            DataIncompatibleWithUsernameGenerationException::class
        );
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
        $rule = \Mockery::spy(\Rule_UserName::class);
        $rule->shouldReceive('isUnixValid')->andReturns(false, true);
        $rule->shouldReceive('isValid')->andReturns(true);
        $username_generator = new UsernameGenerator($this->userNameNormalizer);

        $username = "gfamilyname";

        $this->userNameNormalizer->shouldReceive("normalize")->andThrow(
            DataIncompatibleWithUsernameGenerationException::class
        )->once();

        $this->userNameNormalizer->shouldReceive("normalize")->withArgs([$username])->andReturn(
            $username
        );

        $generated_username = $username_generator->getUsername(
            [
                'preferred_username' => 'incompatiblepreferredusername',
                'given_name'         => 'Given Name',
                'family_name'        => 'Family Name',
            ]
        );
        $this->assertEquals($username, $generated_username);
    }
}
