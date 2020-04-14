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

namespace Tuleap\OAuth2Server\User;

use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;

final class UserInfoResponseRepresentationTest extends TestCase
{
    use ForgeConfigSandbox;

    protected function setUp(): void
    {
        \ForgeConfig::set('sys_https_host', 'tuleap.example.com');
    }

    /**
     * @dataProvider dataProviderClaims
     */
    public function testBuildsRepresentation(
        bool $with_email,
        bool $with_profile,
        string $expected_json
    ): void {
        $user           = UserTestBuilder::aUser()->withId(110)
            ->withEmail('user@example.com')
            ->withStatus(\PFUser::STATUS_ACTIVE)
            ->withUserName('testuser')
            ->withRealName('Test USER')
            ->withTimezone('America/Montreal')
            ->withLocale('en_US')
            ->build();
        $representation = UserInfoResponseRepresentation::fromUserWithSubject($user);
        if ($with_email === true) {
            $representation = $representation->withEmail();
        }
        if ($with_profile === true) {
            $representation = $representation->withProfile();
        }
        $this->assertJsonStringEqualsJsonString(
            $expected_json,
            json_encode($representation, JSON_THROW_ON_ERROR)
        );
    }

    public function dataProviderClaims(): array
    {
        return [
            'With subject claim only' => [
                false,
                false,
                '{"sub":"110"}'
            ],
            'With email'              => [
                true,
                false,
                '{"sub":"110","email":"user@example.com","email_verified":true}'
            ],
            'With profile'            => [
                false,
                true,
                '{"sub":"110","name":"Test USER","preferred_username":"testuser","profile":"https:\/\/tuleap.example.com\/users\/testuser","picture":"https:\/\/\/themes\/common\/images\/avatar_default.png","zoneinfo":"America\/Montreal","locale":"en-US"}'
            ],
            'With all claims'         => [
                true,
                true,
                '{"sub":"110","email":"user@example.com","email_verified":true,"name":"Test USER","preferred_username":"testuser","profile":"https:\/\/tuleap.example.com\/users\/testuser","picture":"https:\/\/\/themes\/common\/images\/avatar_default.png","zoneinfo":"America\/Montreal","locale":"en-US"}'
            ]
        ];
    }
}
