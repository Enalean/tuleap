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
use Tuleap\Test\PHPUnit\TestCase;

final class UserNameNormalizerTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject&Rule_UserName $rules;
    private UserNameNormalizer $username_normalizer;

    protected function setUp(): void
    {
        $this->rules               = $this->createMock(Rule_UserName::class);
        $this->username_normalizer = new UserNameNormalizer($this->rules, new Slugify());
    }

    /**
     * @testWith ["coincoin", "coincoin"]
     *           ["coin_coin", "coin.coin"]
     *           ["coin_coin", "coin coin"]
     *           ["coin_coin", "coin.coin"]
     *           ["coin_coin", "coin:coin"]
     *           ["coin_coin", "coin;coin"]
     *           ["coin_coin", "coin,coin"]
     *           ["coin_coin", "coin?coin"]
     *           ["coin_coin", "coin%coin"]
     *           ["coin_coin", "coin^coin"]
     *           ["coin_coin", "coin*coin"]
     *           ["coin_coin", "coin(coin"]
     *           ["coin_coin", "coin)coin"]
     *           ["coin_coin", "coin{coin"]
     *           ["coin_coin", "coin}coin"]
     *           ["coin_coin", "coin[coin"]
     *           ["coin_coin", "coin]coin"]
     *           ["coin_coin", "coin<coin"]
     *           ["coin_coin", "coin>coin"]
     *           ["coin_coin", "coin+coin"]
     *           ["coin_coin", "coin=coin"]
     *           ["coin_coin", "coin$coin"]
     *           ["coin_coin", "coin/coin"]
     *           ["badegul", "Badegùl"]
     *           ["christiant", "#çhristïant"]
     */
    public function testGenerateUserLogin(string $expected_username, string $given_username): void
    {
        $this->rules->method('isUnixValid')->with($expected_username)->willReturn(true);
        $this->rules->method('isValid')->with($expected_username)->willReturn(true);
        self::assertEquals($expected_username, $this->username_normalizer->normalize($given_username));
    }

    public function testGenerateThrowExceptionWhenUsernameIsNotUnixValid(): void
    {
        $slugified_username = "666";

        $this->rules->method('atLeastOneChar')->willReturn(true);
        $this->rules->method('isUnixValid')->with($slugified_username)->willReturn(false);
        $this->rules->expects(self::never())->method('isValid');

        $this->expectException(DataIncompatibleWithUsernameGenerationException::class);

        $this->username_normalizer->normalize("666");
    }

    public function testGenerateUserLoginIncrementIfLoginAlreadyExist(): void
    {
        $slugified_username   = "jean_pierre";
        $incremented_username = "jean_pierre1";

        $this->rules->method('isUnixValid')->with($slugified_username)->willReturn(true);
        $this->rules->method('isValid')->willReturnMap([
            [$slugified_username, false],
            [$incremented_username, true],
        ]);

        self::assertSame($incremented_username, $this->username_normalizer->normalize("jean pierre"));
    }
}
