<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\User;

use Cocur\Slugify\Slugify;
use Rule_UserName;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserNameNormalizerTest extends TestCase
{
    private UserNameNormalizer $username_normalizer;

    #[\Override]
    protected function setUp(): void
    {
        $user_jean_pierre   = UserTestBuilder::aUser()->withUserName('jean_pierre')->build();
        $user_long_username = UserTestBuilder::aUser()->withUserName(str_repeat('a', 30))->build();
        $user_retriever     = ProvideAndRetrieveUserStub::build($user_jean_pierre)->withUsers([$user_jean_pierre, $user_long_username]);
        $project_manager    = $this->createStub(\ProjectManager::class);
        $project_manager->method('getProjectByUnixName')->willReturn(null);
        $system_event_manager = $this->createStub(\SystemEventManager::class);
        $system_event_manager->method('isUserNameAvailable')->willReturn(true);

        $rule = new Rule_UserName(
            $user_retriever,
            $project_manager,
            $system_event_manager,
        );

        $this->username_normalizer = new UserNameNormalizer($rule, new Slugify());
    }

    #[\PHPUnit\Framework\Attributes\TestWith(['coincoin', 'coincoin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin.coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin.coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin:coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin;coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin,coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin?coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin%coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin^coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin*coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin(coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin)coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin{coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin}coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin[coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin]coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin<coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin>coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin+coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin=coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin$coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['coin_coin', 'coin/coin'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['badegul', 'Badegùl'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['christiant', '#çhristïant'])]
    public function testGenerateUserLogin(string $expected_username, string $given_username): void
    {
        self::assertEquals($expected_username, $this->username_normalizer->normalize($given_username));
    }

    public function testGenerateThrowExceptionWhenUsernameIsNotUnixValid(): void
    {
        $this->expectException(DataIncompatibleWithUsernameGenerationException::class);

        $this->username_normalizer->normalize('www');
    }

    public function testGenerateUserLoginIncrementIfLoginAlreadyExist(): void
    {
        self::assertSame('jean_pierre1', $this->username_normalizer->normalize('jean pierre'));
    }

    public function testSupportLongUsernames(): void
    {
        self::assertSame(
            str_repeat('a', 29) . '1',
            $this->username_normalizer->normalize(str_repeat('a', 30))
        );
    }

    public function testSupportShortUsername(): void
    {
        self::assertSame(
            'a11',
            $this->username_normalizer->normalize('a')
        );
    }
}
