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

namespace common\User;

use Cocur\Slugify\Slugify;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Rule_UserName;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\DataIncompatibleWithUsernameGenerationException;
use Tuleap\User\UserNameNormalizer;

class UserNameNormalizerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private \Mockery\LegacyMockInterface|Rule_UserName|\Mockery\MockInterface $rules;
    private UserNameNormalizer $username_normalizer;

    protected function setUp(): void
    {
        $this->rules               = \Mockery::mock(Rule_UserName::class);
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
        $this->rules->shouldReceive('isUnixValid')->with($expected_username)->andReturn(true);
        $this->rules->shouldReceive('isValid')->with($expected_username)->andReturn(true);
        $this->assertEquals($expected_username, $this->username_normalizer->normalize($given_username));
    }

    public function testGenerateThrowExceptionWhenUsernameIsNotUnixValid(): void
    {
        $slugified_username = "666";

        $this->rules->shouldReceive('atLeastOneChar')->andReturn(true);
        $this->rules->shouldReceive('isUnixValid')->with($slugified_username)->andReturn(false);
        $this->rules->shouldReceive('isValid')->never();

        $this->expectException(DataIncompatibleWithUsernameGenerationException::class);

        $this->username_normalizer->normalize("666");
    }

    public function testGenerateUserLoginIncrementIfLoginAlreadyExist(): void
    {
        $slugified_username   = "jean_pierre";
        $incremented_username = "jean_pierre1";
        $this->rules->shouldReceive('isUnixValid')->with($slugified_username)->andReturn(true);

        $this->rules->shouldReceive('isValid')->with($slugified_username)->andReturn(false)->once();
        $this->rules->shouldReceive('isValid')->with($incremented_username)->andReturn(true)->once();

        $this->assertSame($incremented_username, $this->username_normalizer->normalize("jean pierre"));
    }
}
