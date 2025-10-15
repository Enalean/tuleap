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
use PHPUnit\Framework\Attributes\DataProvider;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;
use Tuleap\User\DataIncompatibleWithUsernameGenerationException;
use Tuleap\User\UserNameNormalizer;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UsernameGeneratorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    #[DataProvider('dataProviderUserInformationExpectedUserName')]
    public function testGeneratesUsernameFromUserInformation(array $user_information, string $expected_username): void
    {
        $project_manager = $this->createStub(\ProjectManager::class);
        $project_manager->method('getProjectByUnixName')->willReturn(null);
        $system_event_manager = $this->createStub(\SystemEventManager::class);
        $system_event_manager->method('isUserNameAvailable')->willReturn(true);
        $rule = new \Rule_UserName(
            ProvideAndRetrieveUserStub::build(UserTestBuilder::aUser()->build()),
            $project_manager,
            $system_event_manager,
        );

        $username_generator = new UsernameGenerator(new UserNameNormalizer($rule, new Slugify()));

        $generated_username = $username_generator->getUsername($user_information);

        self::assertSame($expected_username, $generated_username);
    }

    public static function dataProviderUserInformationExpectedUserName(): array
    {
        return [
            'From name' => [
                ['name' => 'Some Name'],
                'somename',
            ],
            'From email' => [
                ['email' => '"Some.na me"@example.com'],
                'some_name',
            ],
            'From a preferred username' => [
                ['preferred_username' => 'mypreferredusername'],
                'mypreferredusername',
            ],
            'From given and family names' => [
                ['given_name' => 'Given Name', 'family_name' => 'Family Name'],
                'gfamilyname',
            ],
            'From family name' => [
                ['family_name' => 'Family Name'],
                'familyname',
            ],
            'From given name' => [
                ['given_name' => 'Given Name'],
                'givenname',
            ],
        ];
    }

    public function testItNeedsAtLeastGivenOrFamilyNamesToGenerateUsername(): void
    {
        $rule = $this->createMock(\Rule_UserName::class);
        $rule->method('isUnixValid')->willReturn(true);
        $rule->method('isValid')->willReturn(true);
        $username_generator = new UsernameGenerator(new UserNameNormalizer($rule, new Slugify()));

        $this->expectException(NotEnoughDataToGenerateUsernameException::class);
        $username_generator->getUsername([]);
    }

    public function testItNeedsDataCompatibleWithUnixUsername(): void
    {
        $rule = $this->createMock(\Rule_UserName::class);
        $rule->method('isUnixValid')->willReturn(false);
        $rule->method('isReservedName')->willReturn(false);
        $rule->method('getErrorMessage')->willReturn('');
        $rule->method('isValid')->willReturn(true);
        $username_generator = new UsernameGenerator(new UserNameNormalizer($rule, new Slugify()));

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
        $rulename = $this->createMock(\Rule_UserName::class);
        $rulename->method('isUnixValid')->willReturnCallback(static fn (string $name): bool => match ($name) {
            'incompatiblepreferredusername' => false,
            'gfamilyname' => true,
        });
        $rulename->method('isReservedName')->willReturn(false);
        $rulename->method('isValid')->willReturnCallback(static fn (string $name): bool => match ($name) {
            'gfamilyname' => true,
        });
        $rulename->method('getErrorMessage')->willReturn('');
        $username_generator = new UsernameGenerator(new UserNameNormalizer($rulename, new Slugify()));

        $username = 'gfamilyname';

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
